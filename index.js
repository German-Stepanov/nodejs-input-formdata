var Input = function (config) {
	//Текущий объект
	var self = this;

	//Умолчания
	config = config || {};
	//Абсолютный адрес временных файлов
	config.tmpDir 		= config.tmpDir || require('path').dirname(require.main.filename) + '/node_modules/';
	//Ограничение данных (0-нет), байт
	config.maxSize  	= config.maxSize || 0;		
	//Срок хранения временных файлов, сек
	config.storageTime 	= config.storageTime || 60;
	//Режим отладки	(Не сохранять отладочную информацию)
	config.isDebug		= config.isDebug==null ? false : Boolean(config.isDebug);					

	//Проверка существования папки для временных файлов
	if (require('fs').existsSync(config.tmpDir)==false) {
		console.error('ERROR INPUT: Не определена директория для временных файлов');
	};
	

	//Возвращает уникальное название временного файла
	this.createTmpName = function () {
		var count_chars = 10;
		return config.tmpDir + Math.random().toString(36).slice(2, 2 + Math.max(1, Math.min(count_chars, 20))) + '_' + Date.now() + '.tmp';
	};

	//Удаление просроченных временных файлов
	this.cleanTmpDir = function () {
		require('fs').readdirSync(config.tmpDir).forEach (function (filename) {
			if (/^.*\.tmp$/.test(filename) && (new Date().getTime() - require('fs').statSync(config.tmpDir + filename).birthtime.getTime() > config.storageTime*1000)) {
				//Удаление просроченного файла
				require('fs').unlinkSync(config.tmpDir + filename);
			};
		});
	};

	//Формирование переменной по данным строки field и установка значения value
	this.saveFieldValue = function (req, input_field, input_value) {
		//Пропускаем поля с пустым именем
		if (!input_field) return;
	
		//Попытка парсить значение
		try {
			input_value = JSON.parse(input_value);
		} catch (e) {
			if ((input_value*1).toString()==input_value.toString()) input_value*=1;
		}

		//Разбиваем поле
		var fields = [];
		input_field.replace(/([^\.\[\]]+)($|\.|\[|\])/g, function (s, name, after) {
			fields.push({
				name : name,
				after: after,
			});
		});
		var result = req.input.parse;
		for (var i=0; i<fields.length; i++) {
			var after  	= fields[i]['after'];
			var name 	= fields[i]['name'];
			
			if (!after) {
				result[name] = input_value;
			} else if (after=='[') {
				if ((result[name] instanceof Array)==false) {
					result[name] = [];
				};
				if (fields[i+1]) {
					result[name][fields[i+1]['name']] = input_value;
				} else {
					result[name].push(input_value);
				};
				break
			} else if (after=='.') {
				if (!result[name]) {
					result[name] = {};
				};
				result = result[name];
			};
		};
	};

	//Функция парсинга принимаемых данных
	this.chunkParse = function (req, chunk, boundary) {
		if (!chunk) return;
		if (config.isDebug) console.log('DEBUG INPUT:', '----------Получена порция данных ' + Math.round(chunk.length/1024) + ' кбайт', 'boundary="' + boundary + '"' );
		if (!boundary) {
			//Формируем строку параметров из полученных данных
			var str = require('querystring').unescape(chunk.toString('utf8'));
			if (config.isDebug) console.log('DEBUG INPUT', str);
			if (config.isDebug) console.log('DEBUG INPUT', '----------Парсинг данных:')
			//Парсинг параметров
			str.replace(/\+/g, ' ').replace(/([^=]*)=([^&]*)(?:&|$)/g, function (s, field, value) {
				if (config.isDebug) console.log('DEBUG INPUT:', 'field="' + field + '" value="' + value + '"');
				//Параметры есть - добавляем результат
				self.saveFieldValue(req, field, value);
			});
			return;
		};
		
		//Преобразуем буфер в бинарную строку
		var str = chunk.toString('binary');

		if (str=='--' + boundary + '--\r\n') {
			//Конец данных
			if (config.isDebug) console.log('DEBUG INPUT:', '--<BOUNDARY>--<CR><LN>')
			return;
		};
		//Разбираем границы данных
		var re = new RegExp('(--' + boundary + '\\r\\n)?((?!\\r\\n--' + boundary + '\\r\\n)[\\s\\S]+?)($|(?:\\r\\n--' + boundary + '(?:--)?\\r\\n))', 'g');
		str.replace(re, function (s, boundary_start, block, boundary_end) {
			if (config.isDebug && boundary_start) {
				//Добавляем начальную границу
				console.log('DEBUG INPUT:',boundary_start.replace(boundary, '<BOUNDARY>').replace(/\r\n/g, '<CR><LN>'));
			};
			//Флаг наличия данных Content-Disposition
			var is_content_deposition = false;
			//Разбираем поля Content-Disposition
			block.replace(/(?:Content-Disposition: form-data; name=")([^"]*?)"(?:; filename="([^"]*?)"\r\nContent-Type: (.*))?\r\n\r\n([\s\S]*?)$/, function (s, field, filename, mime, data) {
				//Данные Content-Disposition имеются
				is_content_deposition = true;
				if (filename) {
					if (config.isDebug) console.log('DEBUG INPUT:', block.replace(data, '<BINARY DATA>').replace(/\r\n/g, '<CR><LN>'), '-->Парсинг');
					//Увеличиваем число скаченных файлов
					req.input.files++;
					//Формируем название файла - преобразование в строку utf8 и раскодировка escape-последовательностей
					filename = require('querystring').unescape(new Buffer (filename, 'binary').toString('utf8'));
					//Сохраняемое значение - объект с информацией о файле
					var fullname = filename.replace(/\\+/g,'/').split('/').pop();
					var file_ext = '.' + fullname.split('.').pop();
					var value = {
						//Временное название
						'tmp'	: self.createTmpName(),
						//mime						
						'mime'	: mime,	
						//расширение					
						'ext'	: file_ext,
						//Размер	
						'size'	: data.length,	
						//Название	(имя без расширения)		
						'name'	: fullname.replace(file_ext, ''),
						//Функция перемещения относительно корневой папки
						'replaceTo' : function(filename) {
							require('fs').renameSync(this['tmp'], require('path').dirname(require.main.filename) + filename);
						},
					};
					//Добавление двоичных данных data во временный файл
					require('fs').appendFileSync(value['tmp'], new Buffer(data, 'binary'));
					//Формируем название поля 
					//Преобразование в строку utf8 и раскодировка escape-последовательностей
					field = require('querystring').unescape(new Buffer (field, 'binary').toString('utf8'));
					//Добавляем результат
					self.saveFieldValue(req, field, value);
					//Сохраняем последний файл
					req.input.lastFile = value;
				} else {
					if (config.isDebug) console.log('DEBUG INPUT:', require('querystring').unescape(new Buffer (block, 'binary').toString('utf8')).replace(/\r\n/g, '<CR><LN>'), '-->Парсинг');
					//Сохраняемое значение - строка utf8 с раскодировкой escape-последовательностей
					var value = require('querystring').unescape(new Buffer (data, 'binary').toString('utf8'));
					//Формируем название поля 
					//Преобразование в строку utf8 и раскодировка escape-последовательностей
					field = require('querystring').unescape(new Buffer (field, 'binary').toString('utf8'));
					//Добавляем результат
					self.saveFieldValue(req, field, value);
					//Удаляем сохраненный файл
					req.input.lastFile = null;
				};
			});
			//Если данные Content-Disposition отсутствуют и есть файл - получен блок с бинарными данными файла
			if (!is_content_deposition && req.input.lastFile) {
				//Увеличиваем размер файла
				req.input.lastFile['size']+= block.length;
				//Сохранение двоичных данных "block"
				require('fs').appendFileSync(req.input.lastFile['tmp'], new Buffer(block, 'binary'));								
				//Последний файл
				if (config.isDebug) console.log('DEBUG INPUT:','<BINARY DATA> --> добавлено в файл tmp');
			};
			//Добавляем конечную границу
			if (config.isDebug && boundary_end) {
				console.log('DEBUG INPUT:', boundary_end.replace(boundary, '<BOUNDARY>').replace(/\r\n/g, '<CR><LN>'));
			};
		});
	};
	
	//Парсинг ввода запросов
	this.start = function (req, res, next) {

		//Формируемый объект
		req.input = {
			parse 		: {},
			size 		: 0, //Размер полученных данных (0-без ограничений)
			count		: 0, //Число частей
			files		: 0, //Число файлов
			toString 	: function () {
				return ((Object.keys(this.parse)==0) ? ('нет данных') : (JSON.stringify(this.parse, null, 4))) + ((req.method=='GET') ? ('') : (' (' + this.count + ' частей ' + this.files + ' файлов ' + Math.round(this.size/1024) + ' кбайт)'));
			},
			lastFile 	: null,
		};
		

		//Не полные части данных
		var chunks = '';
	
		
		//Функция обработки события приема данных
		var onData = function(chunk) {
			if (config.isDebug) console.log('DEBUG INPUT:', '----------Вызывано событие "DATA"' );
			//Проверка размера принимаемых данных
			if (config.maxSize>0 && (Number(req.input.size) + Number(chunk.length))>config.maxSize) {
				//Сообщение об ошибке
				console.error('ERROR INPUT: Размер полученных данных ' + req.input.size + ' байт превысил допустимое значение ' + config.maxSize + ' байт');
				//Завершение приема данных и вызов события "end"
				req.removeListener('data', onData);
				return;
			};
			//Размер принимаемых данных
			req.input.size += chunk.length;
			//Общее число частей данных
			req.input.count++;
			//Проверка завершения порции данных
			chunk = chunk.toString('binary');
			var re_1 = new RegExp(boundary, 'g');
			if (/\r\n$/.test(chunk) || req.input.lastFile || re_1.test(chunk) || !boundary) {
				self.chunkParse (req, chunks + chunk, boundary);
				chunks = '';
			} else {
				chunks += chunk;
				if (config.isDebug) console.log('DEBUG INPUT: Не полные данные');
			}
		};
		
		//Функция обработки события завершения приема данных
		var onDataEnd = function() {
			if (config.isDebug) console.log('DEBUG INPUT:', '----------Вызывано событие "END"' );
			//Выделение данных из url и коррекция кириллицы
			var chunk = req.url.replace(/^[^?]*\??/, '');
			//Парсинг данных
			if (chunk) self.chunkParse (req, chunk, null);
			//Если есть загруженные файлы очищаем просроченные
			if (req.input.files) self.cleanTmpDir();
			//Возврат на сервер
			return next();
		};

		//Определяем boundary
		var boundary = null;
		var re_boundary = /multipart\/form-data; boundary=/;
		if (req.headers['content-type'] && re_boundary.test(req.headers['content-type'])) {
			boundary = req.headers['content-type'].replace(re_boundary, '');
		};
		//Устанавливаем обработчик события получения порции данных
		req.addListener('data', onData);
		//Устанавливаем обработчик события завершения получения данных
		req.addListener('end', onDataEnd);
	};
};
module.exports = function (config) {
	return new Input(config);
};
