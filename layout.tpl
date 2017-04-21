{root:}
	<h1>Сравнение прайсов и данных каталога</h1>
	Список прайсов
	<ul>
		{data::head}
	</ul>
	
	{data::body}
	
{head:}
	<li><a href="/-dealers/?name={~key}">{~key}</a><br>
		<i>Ошибки - прайс: <b>{~length(miss)}</b>, каталог: <b>{~length(lose)}</b>. Совпадения: <b>{~length(bingo)}</b></i>
	</li>
{body:}
	<h2>{~key}</h2>
	<h3>Позиции, которых нет в каталоге, но есть в прайсе {~length(miss)}</h3>
	<ul>
		{miss::list}
	</ul>
	<h3>Позиции, которых нет в прайсе, но есть в каталоге {~length(lose)}</h3>
	<ul>
		{lose::list}
	</ul>
	<h3>Позиции есть и в прайсе и в каталоге {~length(bingo)}</h3>
	<ul>
		{bingo::list}
	</ul>
	
{list:}
	<li>{.}</li>