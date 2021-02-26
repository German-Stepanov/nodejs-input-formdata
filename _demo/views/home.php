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
	.red {
		color:red;
	}
	.green {
		color:green;
	}
	.blue {
		color:blue;
	}
	.gray {
		color:gray;
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
	<?php elseif($test==11): ?>
        <div><a href="/">Список тестов</a></div>
        <br />
        <h2>11. Эмуляция расшифровки получаемых данных на стороне сервера
        	<br><span class="red">ВАРИАНТ <?=$subtest || 2?>: <?=$subtest==1 ? 'Cохранение в файл каждой порции данных' : 'Сохранение в файл только всех порций данных'?></span>
        </h2>
        
		<?
			var $saveFileChunk = $subtest==1 ? true : false;
            var $boundary = '----WebKitFormBoundaryolSoV86KrWslVQky';
            var $chunk_array = [
            '------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="file_place"\r\n\r\n\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="file_subject"\r\n\r\n\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="file_note"\r\n\r\n\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="flag_replace"\r\n\r\n0\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="file_persons"\r\n\r\n\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="file_album_id"\r\n\r\n3\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-da',
            'ta; name="file_status"\r\n\r\nНеактивирован\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050001.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<01А>',
            '<01Б>',
            '<01В>',
            '<01Г>',
            '<01Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050002.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<02А>',
            '<02Б>',
            '<02В>',
            '<02Г>',
            '<02Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050003.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<03А>',
            '<03Б>',
            '<03В>',
            '<03Г>',
            '<03Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050004.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<04А>',
            '<04Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050005.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<05А>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050006.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<06А>',
            '<06Б>',
            '<06В>',
            '<06Г>',
            '<06Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050007.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<07А>',
            '<07Б>',
            '<07В>',
            '<07Г>',
            '<07Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050008.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<08А>',
            '<08Б>',
            '<08В>',
            '<08Г>',
            '<08Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050009.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<09А>',
            '<09Б>',
            '<09В>',
            '<09Г>',
            '<09Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050010.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<10А>',
            '<10Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050011.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<11А>',
            '<11Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050012.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<12А>',
            '<12Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050013.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<13А>',
            '<13Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050014.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<14А>',
            '<14Б>',
            '<14В>',
            '<14Г>',
            '<14Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050015.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<15А>',
            '<15Б>',
            '<15В>',
            '<15Г>',
            '<15Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050016.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<16А>',
            '<16Б>',
            '<16В>',
            '<16Г>',
            '<16Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050017.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<17А>',
            '<17Б>',
            '<17В>',
            '<17Г>',
            '<17Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050018.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<18А>',
            '<18Б>',
            '<18В>',
            '<18Г>',
            '<18Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050019.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<19А>',
            '<19Б>',
            '<19В>',
            '<19Г>',
            '<19Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050020.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<20А>',
            '<20Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050021.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<21А>',
            '<21Б>',
            '<21В>',
            '<21Г>',
            '<21Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050022.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<22А>',
            '<22Б>',
            '<22В>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050023.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<23А>',
            '<23Б>',
            '<23В>',
            '<23Г>',
            '<23Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050024.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<24А>',
            '<24Б>',
            '<24В>',
            '<24Г>',
            '<24Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050025.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<25А>',
            '<25Б>',
            '<25В>',
            '<25Г>',
            '<25Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050026.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<26А>',
            '<26Б>',
            '<26В>',
            '<26Г>',
            '<26Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050027.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<27А>',
            '<27Б>',
            '<27В>',
            '<27Г>',
            '<27Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050028.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<28А>',
            '<28Б>',
            '<28В>',
            '<28Г>',
            '<28Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050029.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<29А>',
            '<29Б>',
            '<29В>',
            '<29Г>',
            '<29Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050030.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<30А>',
            '<30Б>',
            '<30В>',
            '<30Г>',
            '<30Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050031.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<31А>',
            '<31Б>',
            '<31Г>',
            '<31Д>',
            '<31Е>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050032.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<32А>',
            '<32Б>',
            '<32В>',
            '<32Г>',
            '<32Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050033.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<33А>',
            '<33Б>',
            '<33В>',
            '<33Г>',
            '<33Д>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050034.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<34А>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="userfiles[]"; filename="20210807_050035.jpg"\r\nContent-Type: image/jpeg\r\n\r\n<35А>',
            '<35Б>\r\n------WebKitFormBoundaryolSoV86KrWslVQky\r\nContent-Disposition: form-data; name="id_array"\r\n\r\n["42922","42923","42936","42942","42948","42949","42950","429',
            '51","42952","42953"]\r\n------WebKitFormBoundaryolSoV86KrWslVQky--\r\n',
            ];
            var $input = {
                parse 		: {},
                files 		: {},
                lastFile 	: null,
            };

			var $re_chunks  = new RegExp('(--' + $boundary + '\\r\\n)?(Content-Disposition: form-data; name="([^"]*?)"(?:; filename="([^"]*?)"\\r\\nContent-Type: (.*))?\\r\\n\\r\\n)([\\s\\S]*?)(\\r\\n)?(--' + $boundary + '(?:--)?\\r\\n|$)', 'g');
			var $re_binary1 = new RegExp('^([\\S\\s]*?)(\\r\\n)(--' + $boundary + '(?:--)?\\r\\n)');
			var $re_binary2 = new RegExp('^([\\S\\s]*?)(\\r\\n)?$');
			var $re_boundary = new RegExp('--' + $boundary + '(--)?\\r\\n', 'g');
            
            var $chunks = '';
			var $log = '';
			$log += '<div class="green">BOUNDARY = `' + $boundary + '`</div><br>'
		?>

		<br>
        <h3><b>ИСХОДНЫЕ ДАННЫЕ (МАССИВ ПОРЦИЙ ДАННЫХ)</b></h3>
        <br>  
        
        <div>chunks = <?=JSON.stringify($chunk_array, null, 4).replace(/\n/g, '<br>')?></div>

        <div>$input.parse = <?=JSON.stringify($input.parse, null, 4).replace(/\n/g, '<br>')?></div>
        <div>$input.files = <?=JSON.stringify($input.files, null, 4).replace(/\n/g, '<br>')?></div>
        
        <?php foreach($chunk_array as $key=>$chunk): ?>
			<?
                $chunks += $chunk;
				$log += '<div class="green">-----chunk-----</div>';

				if ($input.lastFile && $saveFileChunk) {
					if ($re_binary1.test($chunks)) {
						//Блок с двоичными данными И следующим за ними содержимым
						$chunks = $chunks.replace($re_binary1, function(s, binaryData, endData, bound ) {
							//Записываем в файл
							$input.files[$input.lastFile] += binaryData;
							$log += '<div><span class="blue">(BINARY DATA+)</span>' + (endData ? '<b>(CR)(LN)<b>' : '') + (bound ? bound.replace($re_boundary,'--(BOUNDARY)$1(CR)(LN)') : '') + '</div>';
							return '';
						})
					} else if ($re_binary2.test($chunks)) {
						//Блок c двоичными данными (ТОЛЬКО)
						$chunks = $chunks.replace($re_binary2, function(s, binaryData, endData ) {
							if (endData || /Content-Disposition/.test($chunks)) return s;
							//Записываем в файл
							$input.files[$input.lastFile] += binaryData;
							$log += '<div><span class="blue">(BINARY DATA+)</span></div>';
							return '';
						});
					}
				}

				$chunks = $chunks.replace($re_chunks, function(s, bound1, content, name, filename, mime, value, end_data, bound2) {
					var $value_str;
					if (filename) {
						if (!bound2 && !$saveFileChunk) {
							$log += '<div class="red">Не полные данные</div>';
							return s; //Не полные данные
						}
						$input.lastFile = filename;
						//Записываем в файл
						$input.files[$input.lastFile] = value;
						$value_str = '(BINARY DATA)';
					} else {
						$input.lastFile = null;
						if (!bound2) {
							$log += '<div class="red">Не полные данные</div>';
							return s;
						}
						$input.parse[name] = value;
						$value_str 	= value;
					}
					$log += '<div><b>' + (bound1 || '').replace($re_boundary,'--(BOUNDARY)$1(CR)(LN)<br>') + content.replace(/\r\n/g, '(CR)(LN)') + '<span class="blue">' + $value_str + '</span>' + (end_data ? '(CR)(LN)' : '') + bound2.replace($re_boundary,'<br>--(BOUNDARY)$1(CR)(LN)') + '</b></div>';
                    return '';
                });
            ?>    
            
        <?php endforeach; ?>
        
		<br>
        <h3><b>РАСШИФРОВКА</b></h3>
        <br>  
         
        <?=$log?>
        
		<br>
        <h3><b>РЕЗУЛЬТАТ</b></h3>
        <br>  
        
        <div>$input.parse = <?=JSON.stringify($input.parse, null, 4).replace(/\n/g, '<br>')?></div>
        <div>$input.files = <?=JSON.stringify($input.files, null, 4).replace(/\n/g, '<br>')?></div>
        <?php if ($chunks) : ?>
        	<div class="red">ОШИБКА: Остались не расшифорованные данные = <?=$chunks.replace($re_b,'(BOUNDARY)').replace(/\r\n/g, '(CR)(LN)')?></div>
		<?php else: ?>
        	<div class="blue">ВСЕ ДАННЫЕ РАСШИФРОВАНЫ!</div>
		<?php endif; ?>
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
        <div>11. <a href="/11/1">Эмуляция расшифровки получаемых данных на стороне сервера(сохранение каждой порции в файл).</a></div>
        <div>12. <a href="/11/2">Эмуляция расшифровки получаемых данных на стороне сервера(сохранение только целого файла).</a></div>
    <?php endif; ?>
</body>
</html>
