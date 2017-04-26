{doc:}
<!DOCTYPE html>	
<html>
	<head>
		<link rel="stylesheet" href="/-collect/?css" />
	</head>
	<body>
		<div style="margin-top:50px" class="container">{/doc:}</div>
	</body>
</html>
{ROOT:}
	{:doc}
	<h1>Сравнение прайсов и данных каталога</h1>
	Список прайсов
	<ul>
		{data::head}
	</ul>
	{:/doc}
{head:}
	<li><a href="/-dealers/?dealer={~key}">{~key}</a><br>
		<i>Ошибки - прайс: <b>{~length(miss)}</b>, каталог: <b>{~length(lose)}</b>. Совпадения: <b>{~length(bingo)}</b></i>
	</li>
{DEALER:}
	{:doc}
	<a href="/-dealers/">Список прайсов</a>
	<h1>Прайс {dealer}</h1>
	{data:body}
	{:/doc}
	{body:}
		<i>Ошибки - прайс: <b>{~length(miss)}</b>, каталог: <b>{~length(lose)}</b>. Совпадения: <b>{~length(bingo)}</b></i>
		<h2>Ошибки прайса</h2>
		<i>Найдены позиции только в прайсе - <b>{~length(miss)}</b></i>
		<ul>
			{miss::list-miss}
		</ul>
		<h2>Ошибки каталога</h2>
		<i>Найдены позиции только в каталоге - <b>{~length(lose)}</b></i>
		<table class="table table-striped">
			<tr><th>Каталог</th><th>Ключ Каталога</th><th>Хэш</th></tr>
			{lose::list-lose}
		</table>

		<h2>Совпадения</h2>
		<i>Найдны позиции и в прайсе, и в каталоге - <b>{~length(bingo)}</b></i>
		<table class="table table-striped">
			<tr><th>Каталог</th><th>Ключ Каталога</th><th>Ключ Прайса</th><th>Хэш</th></tr>
			{bingo::list-bingo}
		</table>
		<h2>Картинки без совпадений</h2>
		<table class="table table-striped">
			{images::images}
		</table>
		{images:}
			<tr><td>{~key}</td><td>{::image}</td></tr>
		{list-miss:}
			<li>{price.dealerorig}</li>
		{list-lose:}
			<tr><td><a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a><br>{catalog.images::image}</td><td>{catalog.dealerorig}</td><td>{catalog.dealerkey}</td></tr>
		{list-bingo:}
			<tr><td><a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a><br>{catalog.images::image}</td><td>{catalog.dealerorig}</td><td>{price.dealerorig}</td><td>{price.dealerkey}</td></tr>
		{image:}
			<img title="{.}" src="/-imager/?src={.}&h=50">