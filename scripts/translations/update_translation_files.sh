HERE=`dirname $0`
ROOT="$HERE/../.."
msgmerge -U "$ROOT/languages/rokka-wordpress-plugin-de_CH.po" "$ROOT/languages/rokka-wordpress-plugin.pot"
msgmerge -U "$ROOT/languages/rokka-wordpress-plugin-de_DE.po" "$ROOT/languages/rokka-wordpress-plugin.pot"
