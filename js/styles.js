var themeSettings = (localStorage.getItem('themeSettings')) ? JSON.parse(localStorage.getItem('themeSettings')) :
{};
var themeName = themeSettings.themeName || '';
if (themeName)
{
    document.write('<link rel="stylesheet" id="theme-style" href="css/app-' + themeName + '.css">');
}
else
{
    document.write('<link rel="stylesheet" id="theme-style" href="css/app.css">');
}

