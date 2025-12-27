<?php

use MediaWiki\Html\FormOptions;
use MediaWiki\Html\Html;
use MediaWiki\Linker\Linker;
use MediaWiki\MediaWikiServices;
use MediaWiki\RecentChanges\ChangesList;
use MediaWiki\RecentChanges\RecentChange;

class SpecialSimpleChanges extends SpecialRecentChanges {
	public function __construct() {
		parent::__construct();
		$this->mName = 'SimpleChanges';
	}

	/**
	 * Add our own modifications to the RC query
	 *
	 * @global array $wgContentNamespaces
	 * @global bool $wgSimpleChangesOnlyContentNamespaces
	 * @global bool $wgSimpleChangesOnlyLatest
	 *
	 * @inheritDoc
	 */
	protected function runMainQueryHook(
		&$tables,
		&$fields,
		&$conds,
		&$query_options,
		&$join_conds,
		$opts
	) {
		global $wgContentNamespaces, $wgSimpleChangesOnlyContentNamespaces, $wgSimpleChangesOnlyLatest;

		# don't count log entries toward limit of number of changes displayed
		$conds[] = $this->getDB()->expr( 'rc_source', '!=', RecentChange::SRC_LOG );

		if ( $opts['namespace'] == '' && $wgSimpleChangesOnlyContentNamespaces &&
			$wgContentNamespaces != null ) {
			$contentNamespaces = $wgContentNamespaces;

			$condition = '(rc_namespace = ' . array_shift( $contentNamespaces );
			foreach ( $contentNamespaces as $namespace ) {
				$condition .= ' OR rc_namespace = ' . $namespace;
			}
			$condition .= ')';
			$conds[] = $condition;
		}
		if ( $wgSimpleChangesOnlyLatest ) {
			$conds[] = 'rc_this_oldid=page_latest';

			// Sometimes this is added by the parent, sometimes not.
			if ( !in_array( 'page', $tables ) ) {
				$tables[] = 'page';
				$fields[] = 'page_latest';
				$join_conds['page'] = [ 'LEFT JOIN', 'rc_cur_id=page_id' ];
			}
		}

		return parent::runMainQueryHook( $tables, $fields, $conds, $query_options, $join_conds, $opts );
	}

	/**
	 * Creates the choose namespace selection
	 *
	 * @param FormOptions $opts
	 * @return string
	 */
	protected function namespaceFilterForm( FormOptions $opts ) {
		global $wgSimpleChangesOnlyContentNamespaces;

		if ( !$wgSimpleChangesOnlyContentNamespaces ) {
			return parent::namespaceFilterForm( $opts );
		}
		$namespaceInfo = MediaWikiServices::getInstance()->getNamespaceInfo();
		$nonContentNamespaces = array_diff( $namespaceInfo->getValidNamespaces(),
			$namespaceInfo->getContentNamespaces() );
		// Borrowed from parent class.
		// If $wgSimpleChangesOnlyContentNamespaces is true, we need to change the namespace
		// selector to only show content namespaces.
		$nsSelect = Html::namespaceSelector(
				[ 'selected' => $opts['namespace'], 'all' => '', 'exclude' => $nonContentNamespaces ],
				[ 'name' => 'namespace', 'id' => 'namespace' ]
		);
		$nsLabel = Html::label( $this->msg( 'simplechanges-contentnamespace' )->text(), 'namespace' );
		$invert = Html::check(
			'invert', $opts['invert'],
			[ 'id' => 'nsinvert', 'title' => $this->msg( 'tooltip-invert' )->text() ]
		) . "\u{00A0}" . Html::label(
			$this->msg( 'invert' )->text(),
			'nsinvert',
			[ 'title' => $this->msg( 'tooltip-invert' )->text() ]
		);
		$associated = Html::check(
			'associated',
			$opts['associated'],
			[ 'id' => 'nsassociated', 'title' => $this->msg( 'tooltip-namespace_association' )->text() ]
		) . "\u{00A0}" . Html::label(
			$this->msg( 'namespace_association' )->text(),
			'nsassociated',
			[ 'title' => $this->msg( 'tooltip-namespace_association' )->text() ]
		);

		return [ $nsLabel, "$nsSelect $invert $associated" ];
	}

	/**
	 * Send output to the OutputPage object, only called if not used feeds
	 * This function is a modified combination of SpecialRecentchanges::outputChangesList() &
	 * ChangesList::recentChangesLine()
	 *
	 * @global bool $wgSimpleChangesShowUser
	 * @param array $rows Array of database rows
	 * @param FormOptions $opts
	 */
	public function outputChangesList( $rows, $opts ) {
		$limit = $opts['limit'];

		$counter = 1;
		$list = ChangesList::newFromContext( $this->getContext() );

		$rclistOutput = $list->beginRecentChangesList();

		$rclistOutput .= "\n<ul class=\"special\">\n";
		foreach ( $rows as $obj ) {
			if ( $limit == 0 ) {
				break;
			}
			$rc = RecentChange::newFromRow( $obj );
			$rc->counter = $counter++;

			$changeLine = false;

			if ( $rc->getAttribute( 'rc_log_type' ) ) {
				// Log entries (old format) or log targets, and special pages
			} elseif ( $rc->getAttribute( 'rc_namespace' ) == NS_SPECIAL ) {
				// Regular entries
			} else {
				$changeLine .= $list->getArticleLink( $rc, false, false );
				$changeLine = Html::openElement( 'li' ) . $changeLine;

				global $wgSimpleChangesShowUser;
				if ( $wgSimpleChangesShowUser ) {
					# from ChangesList::insertUserRelatedLinks()
					$user = $rc->getPerformerIdentity();
					$userHtml = ' (' .
						Linker::userLink( $user->getId(), $user->getName() ) . ')';
					$changeLine .= Html::rawElement( 'span', [ 'class' => 'simplechanges-user' ], $userHtml );
				}
				$changeLine .= Html::closeElement( 'li' ) . "\n";
			}

			if ( $changeLine !== false ) {
				$rclistOutput .= $changeLine;
				--$limit;
			}
		}
		$rclistOutput .= "\n</ul>\n";
		$rclistOutput .= $list->endRecentChangesList();
		$this->getOutput()->addHTML( $rclistOutput );
	}
}
