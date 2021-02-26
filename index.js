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
	//Сохранять файл по частям
	config.saveFileChunk= config.saveFileChunk==null ? true : Boolean(config.saveFileChunk);

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
				if ((result[name] instanceof Array)==false) result[name] = [];
				if (fields[i+1]) {
					result[name][fields[i+1]['name']] = input_value;
				} else {
					result[name].push(input_value);
				};
				break
			} else if (after=='.') {
				if (!result[name]) result[name] = {};
				result = result[name];
			};
		};
	};

	//Данные GET
	this.parseNotBoundaryChunk = function(req, chunk) {
		//Формируем строку параметров из полученных данных
		var str = require('querystring').unescape(chunk.toString('utf8'));
		//Парсинг параметров
		str.replace(/\+/g, ' ').replace(/([^=]*)=([^&]*)(?:&|$)/g, function (s, field, value) {
			if (config.isDebug) console.log('DEBUG INPUT:', 'field="' + field + '" value="' + value + '"');
			//Параметры есть - добавляем результат
			self.saveFieldValue(req, field, value);
		});
	};
	
	//Парсинг ввода запросов
	this.start = function (req, res, next) {

		var show = function(str) {
			str = require('querystring').unescape(new Buffer (str, 'binary').toString('utf8'));
			return '`' + str.replace(new RegExp(boundary, 'g'), '<BOUNDARY>').replace(/\r\n/g, '<CR><LN>') + '`';
		}

		//Определяем boundary
		var boundary = null;
		var re_boundary = /multipart\/form-data; boundary=/;
		if (req.headers['content-type'] && re_boundary.test(req.headers['content-type'])) {
			boundary = req.headers['content-type'].replace(re_boundary, '');
		};
		
		if (config.isDebug) console.log(['boundary=' + boundary]);

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

		var re_chunks  = new RegExp('(--' + boundary + '\\r\\n)?(Content-Disposition: form-data; name="([^"]*?)"(?:; filename="([^"]*?)"\\r\\nContent-Type: (.*))?\\r\\n\\r\\n)([\\s\\S]*?)(\\r\\n)?(--' + boundary + '(?:--)?\\r\\n|$)', 'g');
		var re_binary1 = new RegExp('^([\\S\\s]*?)(\\r\\n)(--' + boundary + '(?:--)?\\r\\n)');
		var re_binary2 = new RegExp('^([\\S\\s]*?)(\\r\\n)?$');
		var re_boundary = new RegExp('--' + boundary + '(--)?\\r\\n', 'g');

		//Функция обработки события приема данных
		var onData = function(chunk) {

			//Размер принимаемых данных
			req.input.size += chunk.length;
			//Общее число частей данных
			req.input.count++;

			chunk = chunk.toString('binary');

			if (!boundary) {
				self.parseNotBoundaryChunk(req, chunk);
				return;
			};
			
			//Проверка размера принимаемых данных
			if (config.maxSize>0 && (Number(req.input.size) + Number(chunk.length))>config.maxSize) {
				//Сообщение об ошибке
				console.error('ERROR INPUT: Размер полученных данных ' + req.input.size + ' байт превысил допустимое значение ' + config.maxSize + ' байт');
				//Завершение приема данных и вызов события "end"
				req.removeListener('data', onData);
				return;
			}

			chunks += chunk;
			
			if (config.isDebug) console.log(['chunk ' + chunk.length + ' байт']);
			
			//Извлекаем двоичные данные
			if (req.input.lastFile && config.saveFileChunk) {
				if (re_binary1.test(chunks)) {
					//Блок с двоичными данными И следующим за ними содержимым
					chunks = chunks.replace(re_binary1, function(s, binaryData, endData, bound ) {
						var binaryBuffer = Buffer.from(binaryData, 'binary');
						//Увеличиваем размер файла
						req.input.lastFile['size']+= binaryBuffer.length;
						//Сохранение двоичных данных
						require('fs').appendFileSync(req.input.lastFile['tmp'], binaryBuffer);								
						if (endData) req.input.lastFile = null;
						if (config.isDebug) process.stdout.write('<BINARY DATA+ ' + binaryBuffer.length + ' байт>' + (endData ? '<CR><LN>' : '') + (bound ? bound.replace(re_boundary,'\r\n--<BOUNDARY>$1<CR><LN>\r\n') : ''));
						return '';
					})
				} else if (re_binary2.test(chunks)) {
					//Блок c двоичными данными (ТОЛЬКО)
					chunks = chunks.replace(re_binary2, function(s, binaryData, endData ) {
						if (endData || /Content-Disposition/.test(chunks)) return s;
						var binaryBuffer = Buffer.from(binaryData, 'binary');
						//Увеличиваем размер файла
						req.input.lastFile['size']+= binaryBuffer.length;
						//Сохранение двоичных данных
						require('fs').appendFileSync(req.input.lastFile['tmp'], binaryBuffer);								
						if (config.isDebug) process.stdout.write('<BINARY DATA+ ' + binaryBuffer.length + ' байт>');
						return '';
					});
				}
			}

			//Извлекаем содержимое
			if (re_chunks.test(chunks)) {
				chunks = chunks.replace(re_chunks, function(s, bound1, content, name, filename, mime, value, end_data, bound2) {
					var value_str;
					var name_str = Buffer.from(name, 'binary').toString('utf8');
					var filename_str = filename ? Buffer.from(filename, 'binary').toString('utf8') : '';
					
					if (filename) {
						if (!bound2 && !config.saveFileChunk) return s; //Не полные данные
						//Увеличиваем число скаченных файлов
						req.input.files++;

						var binaryBuffer = Buffer.from(value, 'binary');
						//Сохраняемое значение - объект с информацией о файле
						var file_name_ext = filename_str.split('/').pop().split('.');
						req.input.lastFile = {
							'tmp'	: self.createTmpName(),		//Временное название
							'mime'	: mime,						//MIME	
							'name'	: file_name_ext[0],			//Название без расширения
							'ext'	: '.' + file_name_ext[1],	//Расширение
							'size'	: binaryBuffer.length,		//Размер
							//Функция перемещения относительно корневой папки
							'replaceTo' : function(filename) {
								require('fs').renameSync(this['tmp'], require('path').dirname(require.main.filename) + filename);
							},
						};
						//Добавление двоичных данных data во временный файл
						require('fs').appendFileSync(req.input.lastFile['tmp'], binaryBuffer);
						//Добавляем результат
						self.saveFieldValue(req, name_str, req.input.lastFile);
						if (bound2) req.input.lastFile = null;
						value_str = '<BINARY DATA ' + binaryBuffer.length + ' байт>';
					} else {
						req.input.lastFile = null;
						if (!bound2) return s; //Не полные данные
						value_str 	= Buffer.from(value, 'binary').toString('utf8');
						//Добавляем результат
						self.saveFieldValue(req, name_str, value_str);
					}
					//Визуализация
					if (config.isDebug) process.stdout.write((bound1 || '').replace(re_boundary,'--<BOUNDARY>$1<CR><LN>\r\n') + content.replace(/\r\n/g, '<CR><LN>') + value_str + (end_data ? '<CR><LN>' : '') + bound2.replace(re_boundary,'\r\n--<BOUNDARY>$1<CR><LN>\r\n'));
					return '';
				})
			}
		};

		//Функция обработки события завершения приема данных
		var onDataEnd = function() {
			if (chunks) {
				console.error(['ERROR INPUT: НЕ ВСЕ ДАННЫЕ РАСШИФРОВАНЫ!']);
				console.error([chunks.replace(re_boundary,'--<BOUNDARY>$1<CR><LN>').replace(/\r\n/g, '<CR><LN>')]);
			}
			self.parseNotBoundaryChunk(req, req.url.replace(/^[^?]*\??/, ''));

			if (config.isDebug) console.log('');
			//Если есть загруженные файлы очищаем просроченные
			if (req.input.files) self.cleanTmpDir();
			//Возврат на сервер
			return next();
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
