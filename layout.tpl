{root:}
	<h1>Сравнение прайсов и данных каталога</h1>
	Список прайсов
	<ul>
		{data::head}
	</ul>
	
	{data::body}
	
{head:}
	<li><a href="/-dealers/?name={~key}">{~key}</a></li>
	
{body:}
	<h2>{~key}</h2>
	<h3>Не добавленные</h3>
	<ul>
		{miss::list}
	</ul>
	<h3>Не актуальные</h3>
	<ul>
		{lose::list}
	</ul>
	
{list:}
	<li>{?????????????}</li>