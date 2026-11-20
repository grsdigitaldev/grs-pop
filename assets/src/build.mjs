/**
 * Build degli script del builder e del blocco (esbuild, JSX classico per WordPress 6.0+).
 *
 * Uso: node assets/src/build.mjs (con esbuild 0.24 in una node_modules di una cartella superiore)
 * (esbuild non è incluso nel plugin: si installa in una cartella di strumenti separata).
 */
import { build } from 'esbuild';
import { fileURLToPath } from 'url';
import path from 'path';

const dir = path.dirname( fileURLToPath( import.meta.url ) );
const common = {
	bundle: true,
	format: 'iife',
	target: [ 'es2017' ],
	jsxFactory: 'wp.element.createElement',
	jsxFragment: 'wp.element.Fragment',
	minifyWhitespace: true,
	minifySyntax: true,
	// I nomi restano leggibili: wp i18n make-pot deve trovare __() nel file compilato.
	minifyIdentifiers: false,
	charset: 'utf8',
	legalComments: 'none',
};

await build( { ...common, entryPoints: [ path.join( dir, 'builder/index.jsx' ) ], outfile: path.join( dir, '../js/builder.js' ) } );
await build( { ...common, entryPoints: [ path.join( dir, 'block/index.jsx' ) ], outfile: path.join( dir, '../js/block.js' ) } );
await build( { entryPoints: [ path.join( dir, '../js/grs-pop.js' ) ], outfile: path.join( dir, '../js/grs-pop.min.js' ), minify: true, target: [ 'es2017' ], charset: 'utf8' } );
await build( { entryPoints: [ path.join( dir, '../css/grs-pop.css' ) ], outfile: path.join( dir, '../css/grs-pop.min.css' ), minify: true, charset: 'utf8', loader: { '.css': 'css' } } );
await build( { entryPoints: [ path.join( dir, '../css/maintenance.css' ) ], outfile: path.join( dir, '../css/maintenance.min.css' ), minify: true, charset: 'utf8', loader: { '.css': 'css' } } );
console.log( 'Build completed.' );
