#! /bin/bash
# See https://github.com/GaryJones/wordpress-plugin-git-flow-svn-deploy for instructions and credits.

echo
echo "WordPress Plugin Git-Flow SVN Deploy v2.0.0-dev"
echo

HERE="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# All paths have to be absolute!
PLUGINSLUG="rokka-image-cdn"
GITPATH="/tmp/$PLUGINSLUG-git"
SVNPATH="/tmp/$PLUGINSLUG-svn"
SVNURL="https://plugins.svn.wordpress.org/$PLUGINSLUG"
SVNURL="https://plugins.svn.wordpress.org/timber-library"
SVNUSER=user
SOURCEPATH="$HERE/.." # this file should be in the base of your git repository
MAINFILE="$PLUGINSLUG.php"

echo "Deploy with following configuration"
echo
echo "Slug: $PLUGINSLUG"
echo "Temporary git path: $GITPATH"
echo "Temporary svn path: $SVNPATH"
echo "Remote SVN repo: $SVNURL"
echo "SVN username: $SVNUSER"
echo "Source path: $SOURCEPATH"
echo "Main file: $MAINFILE"
echo

# Let's begin...
echo ".........................................."
echo
echo "Preparing to deploy WordPress plugin"
echo
echo ".........................................."
echo

# Check version in readme.txt is the same as plugin file after translating both to unix line breaks to work around grep's failure to identify mac line breaks
PLUGINVERSION=`grep "Version:" $SOURCEPATH/$MAINFILE | awk -F' ' '{print $NF}' | tr -d '\r'`
echo "$MAINFILE version: $PLUGINVERSION"
READMEVERSION=`grep "^Stable tag:" $SOURCEPATH/readme.txt | awk -F' ' '{print $NF}' | tr -d '\r'`
echo "readme.txt version: $READMEVERSION"

if [ "$READMEVERSION" = "trunk" ]; then
	echo "Version in readme.txt & $MAINFILE don't match, but Stable tag is trunk. Let's proceed..."
elif [ "$PLUGINVERSION" != "$READMEVERSION" ]; then
	echo "Version in readme.txt & $MAINFILE don't match. Exiting...."
	exit 1;
elif [ "$PLUGINVERSION" = "$READMEVERSION" ]; then
	echo "Versions match in readme.txt and $MAINFILE. Let's proceed..."
fi

echo
echo "Creating local copy of SVN repo trunk ..."
svn checkout $SVNURL $SVNPATH --depth immediates
svn update --quiet $SVNPATH/trunk --set-depth infinity
echo "Clearing SVN repo trunk so we can overwrite it"
rm -rf $SVNPATH/trunk/*


#echo "Ignoring development specific files"
svn propset svn:ignore ".DS_Store
Thumbs.db" "$SVNPATH/trunk/"

echo "Changing to $SOURCEPATH to run git command"
cd $SOURCEPATH

echo "Exporting the HEAD of master from git to the temporary GIT directory"
git checkout-index -a -f --prefix=$GITPATH/

echo "Installing composer packages"
echo "Changing to $GITPATH to install composer packages"
cd $GITPATH
curl -s https://getcomposer.org/installer | php
php composer.phar install --no-dev

echo "Installing node modules"
echo "Changing to $GITPATH to install node modules"
cd $GITPATH
npm install --loglevel error
echo "Running gulp deploy task"
$GITPATH/node_modules/.bin/gulp deploy

echo "Compile translation files"
for file in `find "$GITPATH/languages" -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done

echo "Copying required plugin files to SVN trunk"
cp $GITPATH/index.php $SVNPATH/trunk/
cp $GITPATH/readme.txt $SVNPATH/trunk/
cp $GITPATH/rokka-image-cdn.php $SVNPATH/trunk/
cp $GITPATH/uninstall.php $SVNPATH/trunk/
cp -R $GITPATH/assets $SVNPATH/trunk/
cp -R $GITPATH/languages $SVNPATH/trunk/
cp -R $GITPATH/src $SVNPATH/trunk/
cp -R $GITPATH/vendor $SVNPATH/trunk/

exit 1;

echo "Changing directory to SVN and committing to trunk"
cd $SVNPATH/trunk/
# Delete all files that should not now be added.
svn status | grep -v "^.[ \t]*\..*" | grep "^\!" | awk '{print $2"@"}' | xargs svn del
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2"@"}' | xargs svn add
# Fix image mime-types
svn propset svn:mime-type image/png *.png
svn propset svn:mime-type image/jpeg *.jpg
# Commit all changes
#svn commit --username=$SVNUSER -m "Preparing for $PLUGINVERSION release"

# Support for the /assets folder on the .org repo.
# Make the directory if it doesn't already exist
mkdir -p $SVNPATH/assets/
svn update --quiet $SVNPATH/assets --set-depth infinity
echo "Clearing SVN repo assets so we can overwrite it"
rm -rf $SVNPATH/assets/*
echo "Copying assets fiels to SVN assets"
cp -R $GITPATH/wp-assets/* $SVNPATH/assets/

echo "Updating WordPress plugin repo assets and committing"
cd $SVNPATH/assets/
# Delete all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^\!" | awk '{print $2"@"}' | xargs svn del
# Add all new files that are not set to be ignored
svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2"@"}' | xargs svn add
#svn update --accept mine-full $SVNPATH/assets/*
# Fix image mime-types
svn propset svn:mime-type image/png *.png
svn propset svn:mime-type image/jpeg *.jpg
# Commit all changes
#svn commit --username=$SVNUSER -m "Updating assets"

echo "Creating new SVN tag and committing it"
cd $SVNPATH
svn update --quiet $SVNPATH/tags/$PLUGINVERSION
svn copy --quiet $SVNPATH/trunk/ $SVNPATH/tags/$PLUGINVERSION/
# Remove assets and trunk directories from tag directory
svn delete --force --quiet $SVNPATH/tags/$PLUGINVERSION/assets
svn delete --force --quiet $SVNPATH/tags/$PLUGINVERSION/trunk
cd $SVNPATH/tags/$PLUGINVERSION
#svn commit --username=$SVNUSER -m "Tagging version $PLUGINVERSION"

echo "Removing temporary directories $SVNPATH and $GITPATH"
cd $SVNPATH
cd ..
rm -rf $SVNPATH/
rm -rf $GITPATH/

echo "*** FIN ***"