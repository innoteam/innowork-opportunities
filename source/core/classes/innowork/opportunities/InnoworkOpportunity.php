<?php

require_once('innowork/core/InnoworkItem.php');
require_once('innowork/core/InnoworkAcl.php');

class InnoworkOpportunity extends InnoworkItem
{
    public $mTable = 'innowork_opportunities';
    public $mNewDispatcher = 'view';
    public $mNewEvent = 'newopportunity';
    public $mShowDispatcher = 'view';
    public $mShowEvent = 'showopportunity';
    public $mNoTrash = false;
    public $mConvertible = true;
    public $mNoLog = false;
    public $_mCreationAcl = InnoworkAcl::TYPE_PUBLIC;
    const OUTCOME_PENDING = 0;
    const OUTCOME_NEGATIVE = 1;
    const OUTCOME_POSITIVE = 2;

    public function __construct($rrootDb, $rdomainDA, $opportunityId = 0)
    {
        parent::__construct($rrootDb, $rdomainDA, 'opportunity', $opportunityId);

        $this->mKeys['opportunity'] = 'text';
        $this->mKeys['companyid'] = 'table:innowork_directory_companies:companyname:integer';
        $this->mKeys['description'] = 'text';
        $this->mKeys['done'] = 'boolean';
        $this->mKeys['duedate'] = 'timestamp';
        $this->mKeys['value'] = 'text';
        $this->mKeys['outcome'] = 'text';
        $this->mKeys['outcometype'] = 'integer';

        $this->mSearchResultKeys[] = 'opportunity';
        $this->mSearchResultKeys[] = 'companyid';
        $this->mSearchResultKeys[] = 'description';
        $this->mSearchResultKeys[] = 'done';
        $this->mSearchResultKeys[] = 'duedate';
        $this->mSearchResultKeys[] = 'value';
        $this->mSearchResultKeys[] = 'outcome';
        $this->mSearchResultKeys[] = 'outcometype';

        $this->mViewableSearchResultKeys[] = 'opportunity';
        $this->mViewableSearchResultKeys[] = 'companyid';
        $this->mViewableSearchResultKeys[] = 'description';
        $this->mViewableSearchResultKeys[] = 'value';
        $this->mViewableSearchResultKeys[] = 'outcome';

        $this->mSearchOrderBy = 'companyid,opportunity';

        $this->mRelatedItemsFields[] = 'opportunityid';

        $this->mGenericFields['companyid'] = 'companyid';
        $this->mGenericFields['projectid'] = '';
        $this->mGenericFields['title'] = 'opportunity';
        $this->mGenericFields['content'] = 'description';
        $this->mGenericFields['binarycontent'] = '';
    }

    public function doCreate(
        $params,
        $userId
        )
    {
        $result = false;

        $params['trashed'] = $this->mrDomainDA->fmtfalse;

            if ( $params['done'] == 'true' ) $params['done'] = $this->mrDomainDA->fmttrue;
            else $params['done'] = $this->mrDomainDA->fmtfalse;

            if (
                !isset($params['companyid'] )
                or !strlen( $params['companyid'] )
                ) $params['companyid'] = '0';

            if (
                !isset($params['outcometype'] )
                or !strlen( $params['outcometype'] )
                ) $params['outcometype'] = InnoworkOpportunity::OUTCOME_PENDING;

        if ( count( $params ) ) {
            $item_id = $this->mrDomainDA->getNextSequenceValue( $this->mTable.'_id_seq' );

            $params['trashed'] = $this->mrDomainDA->fmtfalse;
            /*
            $params['duedate']['year'] = date( 'Y' );
            $params['duedate']['mon'] = date( 'n' );
            $params['duedate']['mday'] = date( 'd' );
            $params['duedate']['hours'] = date( 'H' );
            $params['duedate']['minutes'] = date( 'i' );
            $params['duedate']['seconds'] = date( 's' );
            */

            //$timestamp = $this->mrDomainDA->getTimestampFromDateArray( $date );

            $key_pre = $value_pre = $keys = $values = '';

            while ( list( $key, $val ) = each( $params ) ) {
                $key_pre = ',';
                $value_pre = ',';

                switch ( $key ) {
                case 'opportunity':
                case 'description':
                case 'done':
                case 'trashed':
                case 'value':
                case 'outcome':
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                case 'companyid':
                case 'outcometype':
                    if ( !strlen( $key ) ) $key = 0;
                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$val;
                    break;

                case 'duedate':
                    $val = $this->mrDomainDA->getTimestampFromDateArray( $val );
                    unset( $date_array );

                    $keys .= $key_pre.$key;
                    $values .= $value_pre.$this->mrDomainDA->formatText( $val );
                    break;

                default:
                    break;
                }
            }

            if ( strlen( $values ) ) {
                if ( $this->mrDomainDA->Execute( 'INSERT INTO '.$this->mTable.' '.
                                               '(id,ownerid'.$keys.') '.
                                               'VALUES ('.$item_id.','.
                                               $userId.
                                               $values.')' )
                    )
                {
                    $result = $item_id;
                }
            }
        }

        return $result;
    }

    public function doEdit(
        $params
        )
    {
        $result = false;

        if ( $this->mItemId ) {
            if ( count( $params ) ) {
                $start = 1;
                $update_str = '';

                if ( isset($params['done'] ) ) {
                    if ( $params['done'] == 'true' ) $params['done'] = $this->mrDomainDA->fmttrue;
                    else $params['done'] = $this->mrDomainDA->fmtfalse;
                }

                while ( list( $field, $value ) = each( $params ) ) {
                    if ( $field != 'id' ) {
                        switch ( $field ) {
                        case 'opportunity':
                        case 'description':
                        case 'done':
                        case 'trashed':
                        case 'value':
                        case 'outcome':
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        case 'companyid':
                        case 'outcometype':
                            if ( !strlen( $value ) ) $value = 0;
                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$value;
                            $start = 0;
                            break;

                        case 'duedate':
                            $value = $this->mrDomainDA->getTimestampFromDateArray( $value );
                            unset( $date_array );

                            if ( !$start ) $update_str .= ',';
                            $update_str .= $field.'='.$this->mrDomainDA->formatText( $value );
                            $start = 0;
                            break;

                        default:
                            break;
                        }
                    }
                }

                $query = &$this->mrDomainDA->Execute(
                    'UPDATE '.$this->mTable.' '.
                    'SET '.$update_str.' '.
                    'WHERE id='.$this->mItemId
                    );

                if ( $query ) $result = true;
            }
        }

        return $result;
    }

    public function doRemove($userId)
    {
        $result = FALSE;

        $result = $this->mrDomainDA->Execute(
            'DELETE FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        return $result;
    }

    public function doGetItem($userId)
    {
        $result = FALSE;

        $item_query = &$this->mrDomainDA->Execute(
            'SELECT * '.
            'FROM '.$this->mTable.' '.
            'WHERE id='.$this->mItemId
            );

        if (
            is_object( $item_query )
            and $item_query->getNumberRows()
            )
        {
            $result = $item_query->getFields();
        }

        return $result;
    }

    public function doTrash($arg)
    {
        return true;
    }

    public function doGetSummary()
    {
        $xml_def = '';

        return $xml_def;
    }
}
