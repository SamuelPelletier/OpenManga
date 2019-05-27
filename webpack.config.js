var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()

    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput",
    })
    .enableSassLoader()
    .enableVersioning()
    .addEntry('js/app', './assets/js/app.js')
    .addEntry('js/login', './assets/js/login.js')
    .addEntry('js/show', './assets/js/show.js')
    .addStyleEntry('css/app', ['./assets/scss/app.scss', './assets/css/index.css', './assets/css/neon-color.css'])
    .addStyleEntry('css/admin', ['./assets/scss/admin.scss'])
    .addStyleEntry('css/show', './assets/css/show.css')
    .addStyleEntry('css/disclaimer', './assets/css/disclaimer.css')
    .addStyleEntry('css/about', './assets/css/about.css')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .addAliases({
        "jquery-ui-autocomplete": require('path').resolve(__dirname, 'node_modules/jquery-ui/ui/widgets/autocomplete.js'),
        "jquery-touchswipe": require('path').resolve(__dirname, 'node_modules/jquery-touchswipe/jquery.touchSwipe.js')
    });

module.exports = Encore.getWebpackConfig();
