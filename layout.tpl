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
			<tr><th>Каталог</th><th>Ключ Каталога</th><th>Ключ Прайса</th></tr>
			{lose::list-lose}
		</table>

		<h2>Совпадения</h2>
		<i>Найдны позиции и в прайсе, и в каталоге - <b>{~length(bingo)}</b></i>
		<table class="table table-striped">
			<tr><th>Каталог</th><th>Ключ Каталога</th><th>Ключ Прайса</th></tr>
			{bingo::list-bingo}
		</table>
		

		{list-miss:}
			<li>{price.dealerorig}</li>
		{list-lose:}
			<tr><td><a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a></td><td>{catalog.dealerorig}</td><td>-</td></tr>
		{list-bingo:}
			<tr><td><a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a></td><td>{catalog.dealerorig}</td><td>{price.dealerorig}</td></tr>