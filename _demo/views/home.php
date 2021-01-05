<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Тест</title>
</head>

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
<style>
	form textarea {
		width	: 300px;
		height	: 80px;
	}
	form input, form select {
		width	: 150px;
	}
	form input[type=file] {
		width	: 300px;
	}
	form label {
		display	: inline-block;
		width	: 150px;
	}
</style>
<body>
	<?php if ($test==1): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>1. Форма enctype="application/x-www-form-urlencoded" method="GET"</h2>
        <form enctype="application/x-www-form-urlencoded" method="get" action="/test">
            <div><label>name="name1"</label><input type="text" name="name1" value="1" /></div>
            <div><label>name="name2"</label><input type="text" name="name2" value="example" /></div>
            <div><label>name="name3"</label><input type="text" name="name3" value="2" /></div>
            <div><label>name="name4"</label><textarea name="name4">text text text text text text text text text text</textarea></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
    <?php elseif($test==2): ?>
        <a href="/">Список тестов</a> 
        <br />
        <h2>2. Форма enctype="application/x-www-form-urlencoded" method="POST"</h2>
        <form enctype="application/x-www-form-urlencoded" method="post" action="/test">
            <div><label>name="name1"</label><input type="text" name="name1" value="1" /></div>
            <div><label>name="name2"</label><input type="text" name="name2" value="example" /></div>
            <div><label>name="name3"</label><input type="text" name="name3" value="2" /></div>
            <div><label>name="name4"</label><textarea name="name4">text text text text text text text text text text</textarea></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==3): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>3. Форма enctype="multipart/form-data" method="GET"</h2>
        <form enctype="multipart/form-data" method="get" action="/test">
            <div><label>name="name1"</label><input type="text" name="name1" value="1" /></div>
            <div><label>name="name2"</label><input type="text" name="name2" value="example" /></div>
            <div><label>name="name3"</label><input type="text" name="name3" value="2" /></div>
            <div><label>name="name4"</label><textarea name="name4">text text text text text text text text text text</textarea></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==4): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>4. Форма enctype="multipart/form-data" method="POST". Загрузка одного файла</h2>
        <form enctype="multipart/form-data" method="post" action="/test">
            <div><label>name="field_1"</label><input type="text" name="field_1" value="1" /></div>
            <div><label>name="field_2"</label><input type="text" name="field_2" value="example" /></div>
            <div><label>name="field_3"</label><input type="text" name="field_3" value="2" /></div>
            <div><label>name="field_4"</label><textarea name="field_4">text text text text text text text text text text</textarea></div>
            <div><label>name="userfile"</label><input type="file" name="userfile"/></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==5): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>5. Скобки в названии полей. Загрузка нескольких файлов</h2>
        <form enctype="multipart/form-data" method="post" action="/test">
            <div><label>name="field_1[]"</label><input type="text" name="field_1[]" value="1" /></div>
            <div><label>name="field_1[]"</label><input type="text" name="field_1[]" value="example" /></div>
            <div><label>name="field_2[2]"</label><input type="text" name="field_2[2]" value="2" /></div>
            <div><label>name="field_2[4]"</label><textarea name="field_2[4]">text text text text text text text text text text</textarea></div>
            <div><label>name="userfiles[]"</label><input type="file" name="userfiles[]" multiple/></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==6): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>6. Точка в названии полей</h2>
        <form enctype="multipart/form-data" method="post" action="/test">
            <div><label>name="field_1.a"</label><input type="text" name="field_1.a" value="1" /></div>
			<div><label>name="field_1.b"</label><input type="text" name="field_1.b" value="example" /></div>
            <div><label>name="field_2.c"</label><input type="text" name="field_2.c" value="2" /></div>
            <div><label>name="field_2.d.a"</label><textarea name="field_2.d.a">text text text text text text text text text text</textarea></div>
			<div><label>name="field_2.d.b"</label><select name="field_2.d.b">
            	<option value="0" selected="selected">0</option>
            	<option value="1">1</option>
            	<option value="2">2</option>
            	<option value="3">3</option>
            </select></div>
            <div><label>name="field_2.radio"</label><input type="radio" name="field_2.radio" value="1" checked /></div>
            <div><label>name="field_2.radio"</label><input type="radio" name="field_2.radio" value="2" /></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==7): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>7. Точка и скобки в названии полей</h2>
        <form enctype="multipart/form-data" method="post" action="/test">
            <div><label>name="field_1.a[]"</label><input type="text" name="field_1.a[]" value="1" /></div>
            <div><label>name="field_1.a[]"</label><input type="text" name="field_1.a[]" value="example" /></div>
            <div><label>name="field_2.a[2]"</label><input type="text" name="field_2.a[2]" value="2" /></div>
            <div><label>name="field_1.b[]"</label><input type="checkbox" name="field_1.b[]" value="1" checked /></div>
            <div><label>name="field_1.b[]"</label><input type="checkbox" name="field_1.b[]" value="2" checked /></div>
            <div><label>name="field_1.b[]"</label><input type="checkbox" name="field_1.b[]" value="3" /></div>
            <div><label>name="field_2.c[4]"</label><textarea name="field_2.c[4]">text text text text text text text text text text</textarea></div>
            <div><button type="submit">Отправить форму</button></div>
        </form>
	<?php elseif($test==8): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>8. Использование объекта XMLHttpRequest для отправки данных</h2>
        <div> var data = <?=({'field1.a':'value1','field1.b':'value2','field2':'value3','field3':100}).myFormat()?>
        <div><button id="btn_send" type="button">Отправить данные</button></div>
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
				XHR.Send('/test', data, function(result) {
					document.querySelector('#result').innerHTML = result;
				});
			}
		</script>
	<?php elseif($test==9): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>8. Использование объекта XMLHttpRequest для отправки данных</h2>
        <div> var data = <?=({field1:{a:'value1',b:'value2'},'field2':'value3','field3':100}).myFormat()?>
        <div><button id="btn_send" type="button">Отправить данные</button></div>
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
					'field1' 	: {
						'a': 'value1',
						'b': 'value2'
					},
					'field2' 	: 'value3',
					'field3' 	: 100,
				};
				XHR.Send('/test', data, function(result) {
					document.querySelector('#result').innerHTML = result;
				});
			}
		</script>
	<?php elseif($test==10): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>10. Использование объекта XMLHttpRequest для отправки формы</h2>
        <form enctype="multipart/form-data" method="post" action="/test">
            <div>name="field_1[]":<input type="text" name="field_1[]" value="1" /></div>
            <div>name="field_1[]":<input type="text" name="field_1[]" value="example" /></div>
            <div>name="field_2[2]":<input type="text" name="field_2[2]" value="2" /></div>
            <div>name="field_2[4]":<textarea name="field_2[4]">text text text text text text text text text text</textarea></div>
            <div>name="userfiles[]":<input type="file" name="userfiles[]" multiple/></div>
            <div><button id="btn_send" type="button">Отправить форму</button></div>
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
	<?php else: ?>
        <div>Список тестов</div>
        <br />
        <div>1. <a href="/1">Форма enctype="application/x-www-form-urlencoded" method="GET"</a></div>
        <div>2. <a href="/2">Форма enctype="application/x-www-form-urlencoded" method="POST"</a></div>
        <div>3. <a href="/3">Форма enctype="multipart/form-data" method="GET"</a></div>
        <div>4. <a href="/4">Форма enctype="multipart/form-data" method="POST". Загрузка одного файла</a></div>
        <div>5. <a href="/5">Скобки в названии полей. Загрузка нескольких файлов</a></div>
        <div>6. <a href="/6">Точки в названии полей.</a></div>
        <div>7. <a href="/7">Точки и скобки в названии полей.</a></div>
        <div>8. <a href="/8">Использование объекта XMLHttpRequest для отправки данных.</a></div>
        <div>9. <a href="/9">Использование объекта XMLHttpRequest для отправки данных (вариант).</a></div>
        <div>10. <a href="/10">Использование объекта XMLHttpRequest для отправки формы.</a></div>
    <?php endif; ?>
</body>
</html>
