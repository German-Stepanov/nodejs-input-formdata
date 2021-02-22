# nodejs-input-formdata
Получение и парсинг данных форм.
```
Принимает данные из форм "application/x-www-form-urlencoded"/"multipart/form-data" и формирует объект с полученными данными.
Принимает любое количество файлов из формы "multipart/form-data" методом "POST" в заданную директорию для временного хранения.
Учитывает ограничение на размер получаемых данных.
Автоматически удаляет просроченные файлы из директории для временного хранения.
Формирует функцию для перемещения загруженных файлов.
Позволяет использовать в названии полей формы точку . и/или квадратные скобки [] для помещения полученных данных в объект или в массив.

Метод "start" помещает функционал и полученные данные в req.input.
req.input.parse			- объект с полученными данными полей формы
req.input.parse[имя_поля] 	- значение из поля формы или значения группы полей формы при использовании в названии точки и/или квадратных скобок
```

## Подключение
```JS
//Модуль получения данных
var input = require('input-formdata')(
	//Папка временных (загружаемых) файлов
	tmpDir 		: __dirname + '/_tmp/',
	//Ограничение данных, байт (0-без ограничения)			
	maxSize		: 1024*1024,
	//Срок хранения временных файлов, сек					
	storageTime	: 5*60, 					
	//Режим отладки
	isDebug		: false,						
);

//Формируем задачу
var app = function(req, res) {

	...
		
	//Запуск модуля input -->req.input
	input.start (req, res, function () {
		console.log('Получены данные ' + req.method + ':', req.input.toString());

		...
		console.log(req.input.parse)
	});
};
//Создаем и запускаем сервер для задачи
var server = require('http').createServer(app);
server.listen(2020);
```

## Примеры форм и результат в req.input.parse (полученные данные)

### Пример 1. Форма "application/x-www-form-urlencoded" метод "GET"
```HTML
<form enctype="application/x-www-form-urlencoded" method="get" action="/test">
	<input type="text" name="name1" value="1" />
	<input type="text" name="name2" value="example" />
	<input type="text" name="name3" value="2" />
	<textarea name="name4">text text text text text text text text text text</textarea>
</form>
```
### Результат
```JS
{
	"name1": 1,
	"name2": "example",
	"name3": 2,
	"name4": "text text text text text text text text text text"
}
```


### Пример 2. Форма "application/x-www-form-urlencoded" метод "POST"
```HTML
<form enctype="application/x-www-form-urlencoded" method="post" action="/test">
	<input type="text" name="name1" value="1" />
	<input type="text" name="name2" value="example" />
	<input type="text" name="name3" value="2" />
	<textarea name="name4">text text text text text text text text text text</textarea>
</form>
```
### Результат
```JS
{
	"name1": 1,
	"name2": "example",
	"name3": 2,
	"name4": "text text text text text text text text text text"
}
```


### Пример 3. Форма "multipart/form-data" метод "GET"
```HTML
<form enctype="multipart/form-data" method="get" action="/test">
	<input type="text" name="name1" value="1" />
	<input type="text" name="name2" value="example" />
	<input type="text" name="name3" value="2" />
	<textarea name="name4">text text text text text text text text text text</textarea>
</form>
```
### Результат
```JS
{
	"name1": 1,
	"name2": "example",
	"name3": 2,
	"name4": "text text text text text text text text text text"
}
```


### Пример 4. Форма "multipart/form-data" метод "POST". Отправка одного файла, например "my_file.jpg"
```HTML
<form enctype="multipart/form-data" method="post" action="/test">
	<input type="text" name="field_1" value="1" />
	<input type="text" name="field_2" value="example" />
	<input type="text" name="field_3" value="2" />
	<textarea name="field_4">text text text text text text text text text text</textarea>
	<input type="file" name="userfile"/>
</form>
```
### Результат
```JS
{
    "field_1": 1,
    "field_2": "example",
    "field_3": 2,
    "field_4": "text text text text text text text text text text",
    "userfile": {
        "tmp": "путь_и_случайное_название_файла_в_папке_временного_хранения.tmp",
        "mime": "image/jpeg",
        "ext": ".jpg",
        "size": 471349,
        "name": "my_file"
    }
}
```
### Для перемещения в папку "uploads", например под тем же именем
```JS
if (req.input.parse.userfile) {
	req.input.parse.userfile.replaceTo('/uploads/' + req.input.parse.userfile['name'] + req.input.parse.userfile['ext']);
};
```

### Пример 5. Использование скобок [] в названиях полей. Отправка нескольких файлов, например "my_file_1.jpg" и "my_file_2.jpg" 
```HTML
<form enctype="multipart/form-data" method="post" action="/test">
	<input type="text" name="field_1[]" value="1" />
	<input type="text" name="field_1[]" value="example" />
	<input type="text" name="field_2[2]" value="2" />
	<textarea name="field_2[4]">text text text text text text text text text text</textarea>
	<input type="file" name="userfiles[]" multiple/>
</form>
```
### Результат
```JS
{
    "field_1": [
        1,
        "example"
    ],
    "field_2": [
        null,
        null,
        2,
        null,
        "text text text text text text text text text text"
    ],
    "userfiles": [
        {
            "tmp": "путь_и_случайное_название_файла_в_папке_временного_хранения.tmp",
            "mime": "image/jpeg",
            "ext": ".jpg",
            "size": 441496,
            "name": "my_file_1"
        },
        {
            "tmp": "путь_и_случайное_название_файла_в_папке_временного_хранения.tmp",
            "mime": "image/jpeg",
            "ext": ".jpg",
            "size": 471349,
            "name": "my_file_2"
        }
    ]
}
```
### Для перемещения в папку "uploads", например под теми же именами
```JS
if (req.input.parse.userfiles) {
	for (var key in req.input.parse.userfiles) {
		if (req.input.parse.userfiles[key]) req.input.parse.userfiles[key].replaceTo('/uploads/' + req.input.parse.userfiles[key]['name'] + req.input.parse.userfiles[key]['ext']);
	}
};
```


### Пример 6. Использование точек в названии полей.
```HTML
<form enctype="multipart/form-data" method="post" action="/test">
	<input type="text" name="field_1.a" value="1" />
	<input type="text" name="field_1.b" value="example" />
	<input type="text" name="field_2.c" value="2" /></div>
	<textarea name="field_2.d.a">text text text text text text text text text text</textarea>
	<select name="field_2.d.b">
		<option value="0" selected="selected">0</option>
		<option value="1">1</option>
		<option value="2">2</option>
		<option value="3">3</option>
	</select>
	<input type="radio" name="field_2.radio" value="1" checked />
	<input type="radio" name="field_2.radio" value="2" />
</form>
```
### Результат
```JS
{
    "field_1": {
        "a": 1,
        "b": "example"
    },
    "field_2": {
        "c": 2,
        "d": {
            "a": "text text text text text text text text text text",
            "b": 0
        },
        "radio": 1
    }
}
```

### Пример 7. Использование точек и скобок [] в названии полей.
```HTML
<form enctype="multipart/form-data" method="post" action="/test">
	<input type="text" name="field_1.a[]" value="1" />
	<input type="text" name="field_1.a[]" value="example" />
	<input type="text" name="field_2.a[2]" value="2" />
	<input type="checkbox" name="field_1.b[]" value="1" checked />
	<input type="checkbox" name="field_1.b[]" value="2" checked />
	<input type="checkbox" name="field_1.b[]" value="3" />
	<textarea name="field_2.c[4]">text text text text text text text text text text</textarea>
</form>
```
### Результат
```JS
{
    "field_1": {
        "a": [
            1,
            "example"
        ],
        "b": [
            1,
            2
        ]
    },
    "field_2": {
        "a": [
            null,
            null,
            2
        ],
        "c": [
            null,
            null,
            null,
            null,
            "text text text text text text text text text text"
        ]
    }
}
```
### Пример 8. Использование объекта XMLHttpRequest для отправки данных.
```HTML
<script>
var lib_XHR = function () {
	var self = this;
	this.request = new XMLHttpRequest();
	this.onprogress = function(e) {
		console.log(Math.ceil(e.loaded/e.total*100) + '%');
	};
	this.success = function(result) {
		console.log(result);
	};
	this.onerror = function (e) {
		console.error('ERROR XHR:', '"Вызван request.onerror"', 'Ошибка:', e.toString());
	};
	//Отправка запроса
	this.run = function (url, form_data, success) {
		success 	= (success && typeof(success)=='function') ? (success) : (this.success);
		this.request.open('POST', url, true);
		 //Тип возвращаемых данных
		this.request.responseType = 'text';
		//Принудительно посылаем заголовок
		this.request.setRequestHeader('X-Requested-With', 'XmlHttpRequest');
		//Событие проверки состояния запроса запроса
		this.request.onreadystatechange = function() {
			if (self.request.readyState == 4 && self.request.status == 200) {
				try {
					success(JSON.parse(self.request.responseText));
				} catch (e) {
					success(self.request.responseText);
				}
			};
		};
		//Событие прогресса получения данных
		this.request.upload.onprogress = this.onprogress;
		//Событие ошибки запроса
		this.request.onerror = this.onerror;
		//Отправка запроса
		this.request.send(form_data);
	};
	//Отправка данных
	this.Send = function (url, data, success) {
		success 	= (success && typeof(success)=='function') ? (success) : (this.success);
		data 		= (data && typeof(data)=='object') ? (data) : ({});
		var formData = new FormData();
		for (var field in data) {
			formData.append(field, ((typeof(data[field])=='object' && !(data[field] instanceof File))) ? (JSON.stringify(data[field])) : (data[field]));
		};
		this.run (url, formData, success);
	};
	//Отправка формы
	this.Submit = function (form, success) {
		success = (success && typeof(success)=='function') ? success : this.success;
		if (!form) {
			return success('ERROE XHR.Submit: Форма не обнаружена');
		};
		var url = form.action;
		
		var formData = new FormData();
		for (var i=0; i<form.elements.length; i++) {
			if (!form.elements[i].name) continue;
			if (form.elements[i].files) {
				var keys = Object.keys(form.elements[i].files);
				if (keys.length==0) {
					//Если файлы не выбраны, возвращаем ''
					formData.append(form.elements[i].name,'');
				} else {
					//Если файлы выбраны возвращаем файл
					for (var j in keys) {
						formData.append(form.elements[i].name, form.elements[i].files[j]);
					};
				};
			} else {
				formData.append(form.elements[i].name, form.elements[i].value);
			};
		};
		this.run (url, formData, success);
	};
};
var XHR = new lib_XHR();
</script>

<button id="btn_send" type="button">Отправить данные</button>
<div>Результат:</div>
<div id="result"></div>
<script>
//Событие прогресса запроса
XHR.onprogress = function(e) {
	document.querySelector('#result').innerHTML = Math.ceil(e.loaded/e.total*100) + '%';
};
//Событие нажатия кнопки
document.querySelector('#btn_send').onclick = function(e) {
	var data = {
		'field1.a' 	: 'value1',
		'field1.b' 	: 'value2',
		'field2' 	: 'value3',
		'field3' 	: 100,
	};
	/*
	//или такой вариант
	var data = {
		'field1' 	: {
			'a': 'value1',
			'b': 'value2'
		},
		'field2' 	: 'value3',
		'field3' 	: 100,
	};
	*/	
	XHR.Send('/test', data, function(result) {
		document.querySelector('#result').innerHTML = result;
	});
}
</script>
```
### Результат
```JS
{
    "field1": {
        "a": "value1",
        "b": "value2"
    },
    "field2": "value3",
    "field3": 100
}
```

### Пример 9. Использование объекта XMLHttpRequest для отправки формы с файлами, например "my_file_1.jpg" и "my_file_2.jpg".
```HTML
<form enctype="multipart/form-data" method="post" action="/test">
	<input type="text" name="field_1[]" value="1" />
	<input type="text" name="field_1[]" value="example" />
	<input type="text" name="field_2[2]" value="2" />
	<textarea name="field_2[4]">text text text text text text text text text text</textarea>
	<input type="file" name="userfiles[]" multiple/>
	<button id="btn_send" type="button">Отправить форму</button>
</form>
<div>Результат:</div>
<div id="result"></div>
<script>
//Событие прогресса запроса
XHR.onprogress = function(e) {
	document.querySelector('#result').innerHTML = Math.ceil(e.loaded/e.total*100) + '%';
};
//Событие нажатия кнопки
document.querySelector('#btn_send').onclick = function(e) {
	XHR.Submit(this.form, function(result) {
		document.querySelector('#result').innerHTML = result;
	});
}
</script>
```
### Результат
```JS
{
    "field_1": [
        1,
        "example"
    ],
    "field_2": [
        null,
        null,
        2,
        null,
        "text text text text text text text text text text"
    ],
    "userfiles": [
        {
            "tmp": "путь_и_случайное_название_файла_в_папке_временного_хранения.tmp",
            "mime": "image/jpeg",
            "ext": ".jpg",
            "size": 441496,
            "name": "my_file_1"
        },
        {
            "tmp": "путь_и_случайное_название_файла_в_папке_временного_хранения.tmp",
            "mime": "image/jpeg",
            "ext": ".jpg",
            "size": 471349,
            "name": "my_file_2"
        }
    ]
}
```
### Для перемещения в папку "uploads", например под теми же именами
```JS
if (req.input.parse.userfiles) {
	for (var key in req.input.parse.userfiles) {
		if (req.input.parse.userfiles[key]) req.input.parse.userfiles[key].replaceTo('/uploads/' + req.input.parse.userfiles[key]['name'] + req.input.parse.userfiles[key]['ext']);
	}
};
```

## Тестирование
```
Пример серверного кода для проверки работоспособности расположен в директории "_demo"
Для запуска установите конфигурацию модуля "output-view" (препроцессор шаблонов PHP)
```
### Запуск тестового сервера из папки "input-formdata" или "_demo"
```
npm run demo
```
или из папки "_demo"
```
node server
```
### Результат
```
http://localhost:2020
```
