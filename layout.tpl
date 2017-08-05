{ROOT:}
	<h1>Сравнение прайсов и данных каталога</h1>
	Список прайсов
	<ul>
		{prices::head}
	</ul>
	{head:}
		<li><a href="/{root}/{~key}">{~key}</a><br>
			{data:priceblock}
		</li>
{DOUBLES:}
	<h1>Дубли прайса {price}</h1>
	{data:priceinfo}
	<h2>Дубли прайса</h2>
	{~print(data.doublespr)}
	<h2>Дубли каталога</h2>
	{~print(data.doublescat)}
	{priceinfo:}
		{:priceblock}
		<div>Поиск в прайсе по ключу: <b>{rule.price}</b></div>
		<div>Поиск в каталоге по ключу: <b>{rule.catalog}</b></div>
		<ul>
			<li><a href="/-catalog/check/repeats/{price}">Дубли артикула в каталоге</a></li>
			<li><a href="/{root}/{price}/doubles">Дубли ключа поиска в прайсе и каталоге</a></li>
			<li><a href="/{root}/{price}/show">Шапка прайса</a></li>
		</ul>
	{priceblock:}
		<i>
			Ошибки - прайс: <b>{~length(losecat)}</b>, каталог: <b>{~length(losepr)}</b>. 
			Совпадения: <b>{~length(bingo)}</b>. 
			Дубли - прайс: <b>{doublesprcount}</b>, каталог: <b>{doublescatcount}</b>, артикул: <b>{repeats}</b>.
		</i>
{PRICE:}
	<h1>Прайс {price}</h1>
	{data:body}
	
	{body:}
		{:priceinfo}
		{:showpriceerror}
		{:showcaterror}

		{:showbingo}
		<h2>Картинки без совпадений</h2>
		Всего: {~length(images)}
		<table class="table table-striped">
			{images::images}
		</table>
		{images:}
			<tr><td>{~key}</td><td>{::image}</td></tr>		
		{image:}
			<img title="{.}" src="/-imager/?src={.}&h=50">
	{showpriceerror:}
		<h2>Ошибки прайса</h2>
		<i>Позиции в прайсе без совпадений с каталогом - <b>{~length(losecat)}</b></i>
		<ul>
			{losecat::list-losecat}
		</ul>
		{list-losecat:}
			<li>{price.pricekey}</li>
	{showcaterror:}
		<h2>Ошибки каталога</h2>
		<i>Найдены позиции только в каталоге - <b>{~length(losepr)}</b></i>
		<table class="table table-striped">
			<tr><th>Каталог</th><th>Поиск в прайсе</th></tr>
			{losepr::list-losepr}
		</table>
		{list-losepr:}
			<tr>
			<td>
				<a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a>
				{:info}
				<br>{catalog.images::image}
			</td>
			<td class="danger">{catalog.pricekey}</td></tr>
	{showbingo:}
		<h2>Совпадения</h2>
		<i>Найдны позиции и в прайсе, и в каталоге - <b>{~length(bingo)}</b></i>
		<table class="table table-striped">
			<tr><th>Каталог</th><th>Строка поиска</th></tr>
			{bingo::list-bingo}
		</table>
		{list-bingo:}
			<tr>
				<td>
					<a href="/catalog/{catalog.producer}/{catalog.article}">{catalog.Артикул}</a>
					{:info}
					<br>{catalog.images::image}
				</td>
			<td class="success">{price.pricekey}</td></tr>
	{info:}
		<small style="color:gray; float:right">
			Цена: <b>{~cost(catalog.Цена)} руб.</b>, 
			Код: <b>{catalog.Код}</b>
		</small>
{SHOW:}
	<h1>Шапка прайса {price}</h1>
	<div>Поиск в прайсе: <b>{rule.price}</b></div>
	<div>Поиск в каталоге: <b>{rule.catalog}</b></div>
	{data::exlist}
	{exlist:}
		<h2>{~key}</h2>
		{::exhead}.
		{exhead:}{.}{~last()|:comma}

{comma:}, 