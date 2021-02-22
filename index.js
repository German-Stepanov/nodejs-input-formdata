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

		var re_chunks = new RegExp('(--' + boundary + '\\r\\n)?([\\S\\s]+?)(--' + boundary + '(?:--)?\\r\\n|$)', 'g');
		var re_contents = /^Content-Disposition: ?form-data; ?name="([^"]*?)"(?:; ?filename="([^"]*?)"\r\nContent-Type: ?(.*))?\r\n\r\n([\s\S]*?)$/g;
		var re_endData = /\r\n$/;
		var re_b = new RegExp(boundary, 'g');
		
		//Функция обработки события приема данных
		var onData = function(chunk) {

			//Размер принимаемых данных
			req.input.size += chunk.length;
			//Общее число частей данных
			req.input.count++;

			chunk = chunk.toString('binary');

			if (!boundary) {
				//Формируем строку параметров из полученных данных
				var str = require('querystring').unescape(chunk.toString('utf8'));
				//Парсинг параметров
				str.replace(/\+/g, ' ').replace(/([^=]*)=([^&]*)(?:&|$)/g, function (s, field, value) {
					if (config.isDebug) console.log('DEBUG INPUT:', 'field="' + field + '" value="' + value + '"');
					//Параметры есть - добавляем результат
					self.saveFieldValue(req, field, value);
				});
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
	

			chunks = chunks.replace(re_chunks, function(s, bound1, contents, bound2) {
				//Начальная граница
				if (config.isDebug && bound1) process.stdout.write(bound1.replace(re_b,'<BOUNDARY>').replace(/\r\n/g, '<CR><LN>') + '\r\n');

				var flag = false;
				contents = contents.replace(re_contents, function(s1, name, filename, mime, value) {
					flag = true;
					var end_data = re_endData.test(value);
					
					var name_str 		= name ? Buffer.from(name, 'binary').toString('utf8') : name;
					var filename_str	= filename ? Buffer.from(filename, 'binary').toString('utf8') : filename;
					var value_str 		= value ? Buffer.from(value, 'binary').toString('utf8') : value;
					
					if (filename) {
						var binaryData = value.replace(re_endData, '');
						var binaryBuffer = Buffer.from(binaryData, 'binary');
						//Увеличиваем число скаченных файлов
						req.input.files++;
						//if (config.isDebug) process.stdout.write('(files=' + req.input.files + ')');
						//Сохраняемое значение - объект с информацией о файле
						var file_name_ext = filename_str.split('/').pop().split('.');
						req.input.lastFile = {
							//Временное название
							'tmp'		: self.createTmpName(),
							//mime						
							'mime'		: mime,	
							//Название без расширения
							'name'		: file_name_ext[0],
							//расширение					
							'ext'		: '.' + file_name_ext[1],
							//Размер	
							'size'		: binaryBuffer.length,	
							//Функция перемещения относительно корневой папки
							'replaceTo' : function(filename) {
								require('fs').renameSync(this['tmp'], require('path').dirname(require.main.filename) + filename);
							},
						};
						//Добавление двоичных данных data во временный файл
						require('fs').appendFileSync(req.input.lastFile['tmp'], binaryBuffer);
						//Добавляем результат
						self.saveFieldValue(req, name_str, req.input.lastFile);
						if (end_data) req.input.lastFile = null;
						
						if (config.isDebug) {
							var output = s1.replace(value, '<BINARY DATA>').replace(/\r\n/g, '<CR><LN>');
							output = Buffer.from(output, 'binary').toString('utf8').replace('<BINARY DATA>', '<BINARY DATA ' + binaryBuffer.length + ' байт>');
							process.stdout.write(output);
						}
						return '';
					} else {
						req.input.lastFile = null;
						if (!end_data) {
							process.stdout.write(('Не полные данные'));
							process.stdout.write('\r\n');
							return s1; //Не полные данные
						}
						value_str = value_str.replace(re_endData, '');
						//Добавляем результат
						self.saveFieldValue(req, name_str, value_str);
						if (config.isDebug) {
							var output = s1.replace(/\r\n/g, '<CR><LN>')
							output = Buffer.from(output, 'binary').toString('utf8')
							process.stdout.write(output);
						}
						return '';
					}
				});
				
				if (!flag && req.input.lastFile) {
					var end_data = re_endData.test(contents);
					
					var binaryData = contents.replace(re_endData, '');
					var binaryBuffer = Buffer.from(binaryData, 'binary');
					//Увеличиваем размер файла
					req.input.lastFile['size']+= binaryBuffer.length;
					//Сохранение двоичных данных "block"
					require('fs').appendFileSync(req.input.lastFile['tmp'], binaryBuffer);								
					if (end_data) req.input.lastFile = null;
					if (config.isDebug) process.stdout.write('<BINARY DATA+ ' + binaryBuffer.length + ' байт>' + (end_data ? '<CR><LN>' : ''));
					if (config.isDebug && bound2) process.stdout.write('\r\n' + bound2.replace(re_b,'<BOUNDARY>').replace(/\r\n/g, '<CR><LN>') + '\r\n');
					//Удаляем данные
					return '';
				}
				//Конечная граница
				if (config.isDebug && bound2) process.stdout.write('\r\n' + bound2.replace(re_b,'<BOUNDARY>').replace(/\r\n/g, '<CR><LN>'));
				return (contents) ? (bound1 || '') + contents + bound2 : '';
			});
		};

		//Функция обработки события завершения приема данных
		var onDataEnd = function() {
			if (chunks) {
				console.error(['ERROR: INPUT: НЕ ВСЕ ДАННЫЕ РАСШИФРОВАНЫ!']);
				console.error(chunks.replace(re_b,'<BOUNDARY>').replace(/\r\n/g, '<CR><LN>'));
			}
			var chunk = req.url.replace(/^[^?]*\??/, '');
			//Формируем строку параметров из полученных данных
			var str = require('querystring').unescape(chunk.toString('utf8'));
			//Парсинг параметров
			str.replace(/\+/g, ' ').replace(/([^=]*)=([^&]*)(?:&|$)/g, function (s, field, value) {
				if (config.isDebug) console.log('DEBUG INPUT:', 'field="' + field + '" value="' + value + '"');
				//Параметры есть - добавляем результат
				self.saveFieldValue(req, field, value);
			});
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
