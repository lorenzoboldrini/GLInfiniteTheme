{
	"$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "{{namespace}}/{{slug}}",
	"version": "0.1.0",
	"title": "{{title}}",
	"category": "design",
	"icon": "{{icon}}",
	"description": "",
	"keywords": [],
	"textdomain": "{{textdomain}}",
	"attributes": {},
	"supports": {
		"html": false,
		"anchor": true,
		"align": [ "wide", "full" ],
		"color": {
			"background": true,
			"text": true
		},
		"spacing": {
			"margin": true,
			"padding": true
		},
		"typography": {
			"fontSize": true
		}
	},
#DYNAMIC#	"render": "file:./render.php",
	"editorScript": "file:./index.js",
	"editorStyle": "file:./index.css",
	"style": "file:./style-index.css"
}
