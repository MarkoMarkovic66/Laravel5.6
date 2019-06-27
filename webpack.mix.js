let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

/*
 * laravel-mixではワイルドカードは指定できないので
 * CSS,JSともに個別に指定します。
 */
mix
//CSS
.sass('resources/assets/sass/app.scss', 'public/css')
.sass('resources/assets/sass/style.scss', 'public/css')

//共通JS
.js('resources/assets/js/namespace.js', 'public/js')
.js('resources/assets/js/app.js', 'public/js')
.js('resources/assets/js/ajaxUtils.js', 'public/js')
.js('resources/assets/js/utils.js', 'public/js')


//個別JS
.js('resources/assets/js/main.js', 'public/js')
.js('resources/assets/js/dashboard.js', 'public/js')
.js('resources/assets/js/task.js', 'public/js')
.js('resources/assets/js/member.js', 'public/js')
.js('resources/assets/js/member_detail.js', 'public/js')
.js('resources/assets/js/member_report.js', 'public/js')
.js('resources/assets/js/task_calendar.js', 'public/js')
.js('resources/assets/js/learning_policy.js', 'public/js')



.version();

mix.copyDirectory('resources/assets/lib', 'public/lib');
mix.copyDirectory('resources/assets/images', 'public/images');


