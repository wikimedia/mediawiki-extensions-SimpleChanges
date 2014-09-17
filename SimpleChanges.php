<?php
/**
 * SimpleChanges - Special page that displays a barebones Recent Changes list
 *
 * To activate this extension, add the following into your LocalSettings.php file:
 * require_once "$IP/extensions/SimpleChanges/SimpleChanges.php";
 *
 * @ingroup Extensions
 * @author Ike Hecht
 * @version 0.2
 * @link https://www.mediawiki.org/wiki/Extension:SimpleChanges Documentation
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'SimpleChanges',
	'version' => '0.2',
	'author' => 'Ike Hecht for [http://www.wikiworks.com/ WikiWorks]',
	'url' => 'https://www.mediawiki.org/wiki/Extension:SimpleChanges',
	'descriptionmsg' => 'simplechanges-desc',
);

$wgAutoloadClasses['SpecialSimpleChanges'] = __DIR__ . '/SpecialSimpleChanges.php';
$wgSpecialPages['SimpleChanges'] = 'SpecialSimpleChanges';
$wgExtensionMessagesFiles['SimpleChanges'] = __DIR__ . '/SimpleChanges.i18n.php';
$wgMessageDirs['SimpleChanges'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['SimpleChangesAlias'] = __DIR__ . '/SimpleChanges.alias.php';

# Restrict list of changes to $wgContentNamespaces?
$wgSimpleChangesOnlyContentNamespaces = false;
