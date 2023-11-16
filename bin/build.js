import * as esbuild from 'esbuild';
import { polyfillNode } from 'esbuild-plugin-polyfill-node';

const isDev = process.argv.includes('--dev');

async function compile(options) {
    const context = await esbuild.context(options)

    if (isDev) {
        await context.watch()
    } else {
        await context.rebuild()
        await context.dispose()
    }
}

const defaultOptions = {
    define: {
        'process.env.NODE_ENV': isDev ? `'development'` : `'production'`,
    },
    bundle: true,
    mainFields: ['module', 'main'],
    platform: 'neutral',
    sourcemap: isDev ? 'inline' : false,
    sourcesContent: isDev,
    treeShaking: true,
    target: ['es2020'],
    minify: !isDev,
    plugins: [{
        name: 'watchPlugin',
        setup: function (build) {
            build.onStart(() => {
                console.log(`Build started at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
            })

            build.onEnd((result) => {
                if (result.errors.length > 0) {
                    console.log(`Build failed at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`, result.errors)
                } else {
                    console.log(`Build finished at ${new Date(Date.now()).toLocaleTimeString()}: ${build.initialOptions.outfile}`)
                }
            })
        }
    },
        polyfillNode({
            polyfills: {
                crypto: true,
                fs: true,
            }
        })
    ],
}

compile({
    ...defaultOptions,
    entryPoints: ['./app-modules/assistant/resources/js/assistantCurrentResponse.js'],
    outfile: './app-modules/assistant/resources/js/dist/assistantCurrentResponse.js',
})

compile({
    ...defaultOptions,
    entryPoints: ['./app-modules/in-app-communication/resources/js/userToUserChat.js'],
    outfile: './app-modules/in-app-communication/resources/js/dist/userToUserChat.js',
})