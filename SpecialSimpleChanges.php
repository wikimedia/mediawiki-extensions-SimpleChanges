<?php

class SpecialSimpleChanges extends SpecialRecentChanges {

	public function __construct( $name = 'SimpleChanges' ) {
		parent::__construct( $name );
	}

	/**
	 * Return an array of conditions depending of options set in $opts
	 *
	 * @param $opts FormOptions
	 * @return array
	 */
	public function buildMainQueryConds( FormOptions $opts ) {
		global $wgContentNamespaces, $scOnlyContentNamespaces;

		$conds = parent::buildMainQueryConds( $opts );

		# don't count log entries toward limit of number of changes displayed
		$conds[] = 'rc_type != ' . RC_LOG;

		if( $opts['namespace'] == '' && $scOnlyContentNamespaces && $wgContentNamespaces != null ) {
			$contentNamespaces = $wgContentNamespaces;

			$condition = '(rc_namespace = ' . array_shift( $contentNamespaces );
			foreach ( $contentNamespaces as $namespace ) {
				$condition .= ' OR rc_namespace = ' . $namespace;
			}
			$condition .= ')';
			$conds[] = $condition;
		}

		return  $conds;
	}

	/**
	 * Creates the choose namespace selection
	 *
	 * @todo Uses radio buttons (HASHAR)
	 * @param FormOptions $opts
	 * @return string
	 */
	protected function namespaceFilterForm( FormOptions $opts ) {
		global $scOnlyContentNamespaces;

		if ( !$scOnlyContentNamespaces ) {
			return parent::namespaceFilterForm( $opts );
		}
		$nonContentNamespaces = array_diff ( MWNamespace::getValidNamespaces(), MWNamespace::getContentNamespaces() );
		//borrowed from parent class
		//If scOnlyContentNamespaces is true, we need to change the namespace selector to only show content namespaces.
		$nsSelect = Html::namespaceSelector(
			array( 'selected' => $opts['namespace'], 'all' => '', 'exclude' => $nonContentNamespaces ),
			array( 'name' => 'namespace', 'id' => 'namespace' )
		);
		$nsLabel = Xml::label( $this->msg( 'simplechanges-contentnamespace' )->text(), 'namespace' );
		$invert = Xml::checkLabel(
			$this->msg( 'invert' )->text(), 'invert', 'nsinvert',
			$opts['invert'],
			array( 'title' => $this->msg( 'tooltip-invert' )->text() )
		);
		$associated = Xml::checkLabel(
			$this->msg( 'namespace_association' )->text(), 'associated', 'nsassociated',
			$opts['associated'],
			array( 'title' => $this->msg( 'tooltip-namespace_association' )->text() )
		);

		return array( $nsLabel, "$nsSelect $invert $associated" );
	}

	/**
	 * Send output to the OutputPage object, only called if not used feeds
	 * This function is a modified combination of SpecialRecentchanges::webOutput() (the parent) &
	 * ChangesList::recentChangesLine()
	 *
	 * @param $rows Array of database rows
	 * @param $opts FormOptions
	 */
	public function webOutput( $rows, $opts ) {
		$limit = $opts['limit'];

		if( !$this->including() ) {
			// Output options box - the legend will use message 'recentchanges-legend' which is not ideal.
			$this->doHeader( $opts );
		}

		$counter = 1;
		$list = ChangesList::newFromContext( $this->getContext() );

		$s = $list->beginRecentChangesList();

		$s .= "\n<ul class=\"special\">\n";
		foreach( $rows as $obj ) {
			if( $limit == 0 ) {
				break;
			}
			$rc = RecentChange::newFromRow( $obj );
			$rc->counter = $counter++;

			$classes = array();
			$changeLine = false;

			// Ignore everything other than actual changes to pages
			// Moved pages (very very old, not supported anymore)
			if( $rc->mAttribs['rc_type'] == RC_MOVE || $rc->mAttribs['rc_type'] == RC_MOVE_OVER_REDIRECT ) {
			// Log entries
			} elseif( $rc->mAttribs['rc_log_type'] ) {
			// Log entries (old format) or log targets, and special pages
			} elseif( $rc->mAttribs['rc_namespace'] == NS_SPECIAL ) {
			// Regular entries
			} else {
				$list->insertArticleLink( $changeLine, $rc, false, false );
				$changeLine = "<li class=\"" . implode( ' ', $classes ) . "\">" . $changeLine . "</li>\n";
			}

			if ( $changeLine !== false ) {
				$s .= $changeLine;
				--$limit;
			}
		}
		$s .= "\n</ul>\n";
		$s .= $list->endRecentChangesList();
		$this->getOutput()->addHTML( $s );
	}
}
