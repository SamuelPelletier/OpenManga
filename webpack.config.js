const Encore = require('@symfony/webpack-encore');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()

    // only needed for CDN's or sub-directory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    //.enableStimulusBridge('./assets/controllers.json')

    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())


    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js')
    })
    .enableSassLoader()
    .addEntry('js/app', ['./assets/js/app.js'])
    .addEntry('show', './assets/js/show.js')
    .addEntry('js/tag', './assets/js/tag.js')
    .addEntry('js/form', './assets/js/form.js')
    .addEntry('js/tag-search', './assets/js/tag-search.js')
    .addEntry('js/square', './assets/js/square.js')
    .addStyleEntry('css/app', ['./assets/scss/app.scss', './assets/css/index.css', './assets/css/neon-color.css', './assets/css/button.css'])
    .addStyleEntry('css/show', ['./assets/css/show.css', './node_modules/lightgallery/css/lightgallery.css'])
    .addStyleEntry('css/disclaimer', './assets/css/disclaimer.css')
    .addStyleEntry('css/tag', './assets/css/tag.css')
    .addStyleEntry('css/form', './assets/css/form.css')
    .addStyleEntry('css/account', './assets/css/account.css')
    .addStyleEntry('css/subscribe', './assets/css/subscribe.css')
    .addStyleEntry('css/shop', './assets/css/shop.css')
    .addStyleEntry('css/invoice', './assets/css/invoice.css')
    .addStyleEntry('css/bootstrap-grid', './node_modules/bootstrap/dist/css/bootstrap-grid.css')
    .addStyleEntry('css/tag-search', './assets/css/tag-search.css')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .addAliases({
        "jquery-ui-autocomplete": require('path').resolve(__dirname, 'node_modules/jquery-ui/ui/widgets/autocomplete.js'),
        "jquery-touchswipe": require('path').resolve(__dirname, 'node_modules/jquery-touchswipe/jquery.touchSwipe.js')
    });

module.exports = Encore.getWebpackConfig();
