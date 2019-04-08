var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()

    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput"
    })
    .enableSassLoader()
    .enableVersioning()
    .addEntry('js/app', './assets/js/app.js')
    .addEntry('js/login', './assets/js/login.js')
    .addEntry('js/search', './assets/js/search.js')
    .addEntry('js/show', ['./assets/js/show.js', './node_modules/jquery-touchswipe/jquery.touchSwipe.js'])
    .addStyleEntry('css/app', ['./assets/scss/app.scss', './assets/css/index.css'])
    .addStyleEntry('css/admin', ['./assets/scss/admin.scss'])
    .addStyleEntry('css/show', './assets/css/show.css')
    .addStyleEntry('css/disclaimer', './assets/css/disclaimer.css')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();
