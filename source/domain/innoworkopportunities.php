<?php
// ----- Initialization -----
//
require_once('innowork/opportunities/InnoworkOpportunity.php');
require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/widgets/WuiWidget.php');
require_once('innomatic/wui/widgets/WuiContainerWidget.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/wui/dispatch/WuiEvent.php');
require_once('innomatic/wui/dispatch/WuiEventRawData.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php'); require_once('innomatic/locale/LocaleCountry.php');
require_once('innowork/groupware/InnoworkCompany.php');

global $gXml_def, $gLocale, $gPage_title, $gCompanies;

require_once('innowork/core/InnoworkCore.php');
$gInnowork_core = InnoworkCore::instance('innoworkcore',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
    );

$gLocale = new LocaleCatalog(
    'innowork-opportunities::domain_main',
    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
    );

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomaticpage' );
$gWui->loadWidget( 'innomatictoolbar' );

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr( 'opportunities.title' );
$gCore_toolbars = $gInnowork_core->getMainToolBar();
$gToolbars['view'] = array(
    'mainmenu' => array(
        'label' => $gLocale->getStr( 'mainmenu.toolbar' ),
        'themeimage' => 'listbulletleft',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            '' ) ) )
        ),
    'newopportunity' => array(
        'label' => $gLocale->getStr( 'newopportunity.toolbar' ),
        'themeimage' => 'filenew',
        'horiz' => 'true',
        'action' => WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'newopportunity',
            '' ) ) )
        )
    );

    $innowork_companies = new InnoworkCompany(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );
    $search_results = $innowork_companies->Search(
        '',
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId()
        );

    $gCompanies[0] = $gLocale->getStr( 'nocompany.label' );

    while ( list( $id, $fields ) = each( $search_results ) ) {
        $gCompanies[$id] = $fields['companyname'];
    }

    unset( $innowork_companies );
    unset( $search_results );

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher( 'action' );

$gAction_disp->addEvent(
    'addopportunity',
    'action_addopportunity'
    );
function action_addopportunity(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    $due_date_array = $locale_country->getDateArrayFromShortDatestamp( $eventData['duedate'] );
    $eventData['duedate'] = $due_date_array;

    $opp = new InnoworkOpportunity(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

    $eventData['opportunity'] = str_replace( ',', '.', $eventData['opportunity'] );

    if ( $opp->Create( $eventData ) ) {
        $gPage_status = $gLocale->getStr( 'opp_added.status' );

        $GLOBALS['innowork-opportunities']['newopportunityid'] = $opp->mItemId;
    } else {
        $gPage_status = $gLocale->getStr( 'opp_not_added.status' );
    }
}

$gAction_disp->addEvent(
    'editopportunity',
    'action_editopportunity'
    );
function action_editopportunity(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    $due_date_array = $locale_country->getDateArrayFromShortDatestamp( $eventData['duedate'] );
    $eventData['duedate'] = $due_date_array;

    $opp = new InnoworkOpportunity(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    $eventData['opportunity'] = str_replace( ',', '.', $eventData['opportunity'] );

    if ( $opp->Edit( $eventData ) ) {
        $gPage_status = $gLocale->getStr( 'opp_updated.status' );
    } else {
        $gPage_status = $gLocale->getStr( 'opp_not_updated.status' );
    }
}

$gAction_disp->addEvent(
    'trashopportunity',
    'action_trashopportunity'
    );
function action_trashopportunity(
    $eventData
    )
{
    global $gLocale, $gPage_status;

    $opp = new InnoworkOpportunity(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    $opp->Trash();

    $gPage_status = $gLocale->getStr( 'opp_trashed.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher( 'view' );

function opp_list_action_builder($pageNumber)
{
    return WuiEventsCall::buildEventsCallString( '', array( array(
            'view',
            'default',
            array( 'pagenumber' => $pageNumber )
        ) ) );
}

$gMain_disp->addEvent(
    'default',
    'main_default'
    );
function main_default(
    $eventData
    )
{
    global $gXml_def, $gLocale, $gPage_title, $gCompanies;

    require_once('shared/wui/WuiSessionkey.php');

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    // Lists

    $lists_headers[0]['label'] = $gLocale->getStr( 'opportunities.header' );
    $lists_row = 0;

    // Opportunities

    $opps_row = 0;
    $restrict_to = InnoworkItem::SEARCH_RESTRICT_NONE;
    $limit = 0;

    if ( !isset($eventData['opp_filter'] ) ) {
        $filter_sk = new WuiSessionKey(
            'opp_filter'
            );

        if ( strlen( $filter_sk->mValue ) ) $eventData['opp_filter'] = $filter_sk->mValue;
        else $eventData['opp_filter'] = 'all';
    } else {
        $filter_sk = new WuiSessionKey(
            'opp_filter',
            array(
                'value' => $eventData['opp_filter']
                )
            );
    }

    $opp = new InnoworkOpportunity(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

    $expired = false;

    switch ( $eventData['opp_filter'] ) {
    case 'my':
        $opp_headers[0]['label'] = $gLocale->getStr( 'my_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );

        $restrict_to = InnoworkItem::SEARCH_RESTRICT_TO_OWNER;
        break;

    case 'closed':
        $opp_headers[0]['label'] = $gLocale->getStr( 'closed_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue
            );
        break;

    case 'top':
        $opp_headers[0]['label'] = $gLocale->getStr( 'top_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );
        $opp->mSearchOrderBy = 'value DESC';
        $limit = 10;
        break;

    case 'mytop':
        $opp_headers[0]['label'] = $gLocale->getStr( 'mytop_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );

        $restrict_to = InnoworkItem::SEARCH_RESTRICT_TO_OWNER;
        $opp->mSearchOrderBy = 'value DESC';
        $limit = 10;
        break;

    case 'pending':
        $opp_headers[0]['label'] = $gLocale->getStr( 'pending_opportunities.header' );
        $search_keys = array(
            'outcometype' => InnoworkOpportunity::OUTCOME_PENDING
            );
        break;

    case 'positive':
        $opp_headers[0]['label'] = $gLocale->getStr( 'positive_opportunities.header' );
        $search_keys = array(
            'outcometype' => InnoworkOpportunity::OUTCOME_POSITIVE
            );
        break;

    case 'negative':
        $opp_headers[0]['label'] = $gLocale->getStr( 'negative_opportunities.header' );
        $search_keys = array(
            'outcometype' => InnoworkOpportunity::OUTCOME_NEGATIVE
            );
        break;

    case 'expired':
        $opp_headers[0]['label'] = $gLocale->getStr( 'expired_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );
        $expired = true;
        break;

    case 'all':
    default:
        $opp_headers[0]['label'] = $gLocale->getStr( 'all_opportunities.header' );
        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );
        break;
    }

    $search_results = $opp->Search(
        $search_keys,
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
        false,
        false,
        $limit,
        0,
        $restrict_to
        );

    // Expired check
    if ($expired) {
        $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
        $curr_da = $country->getDateArrayFromUnixTimestamp(time());
        $curr_ts = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getTimestampFromDateArray($curr_da);

        foreach($search_results as $id => $data) {
            if ($data['duedate']>=$curr_ts) {
                unset($search_results[$id]);
            }
        }
        reset( $search_results );
    }

    // Analysis

    $analysis_headers[0]['label'] = $gLocale->getStr( 'analysis.header' ).' - '.$opp_headers[0]['label'];

    $display_array['number'] = $gLocale->getStr( 'display_opportunities_number.label' );
    $display_array['revenue'] = $gLocale->getStr( 'display_opportunities_revenue.label' );
    $display_array['outcome'] = $gLocale->getStr( 'display_opportunities_outcome.label' );

    //$by_array['sector'] = $gLocale->getStr( 'by_sector.label' );
    $by_array['company'] = $gLocale->getStr( 'by_company.label' );

    if ( !isset($eventData['set_analysis'] ) ) {
        $by_analysis_sk = new WuiSessionKey(
            'by_analysis_filter'
            );

        if ( strlen( $by_analysis_sk->mValue ) ) $analysis_by = $by_analysis_sk->mValue;
        else $analysis_by = 'company';

        $display_analysis_sk = new WuiSessionKey(
            'display_analysis_filter'
            );

        if ( strlen( $display_analysis_sk->mValue ) ) $analysis_display = $display_analysis_sk->mValue;
        else $analysis_display = 'number';
    } else {
        if ( !isset($eventData['analysis_by'] ) ) $eventData['analysis_by'] = 'company';

        $by_analysis_sk = new WuiSessionKey(
            'by_analysis_filter',
            array(
                'value' => $eventData['analysis_by']
                )
            );
        $analysis_by = $eventData['analysis_by'];

        $display_analysis_sk = new WuiSessionKey(
            'display_analysis_filter',
            array(
                'value' => $eventData['analysis_display']
                )
            );
        $analysis_display = $eventData['analysis_display'];
    }

    $tmp_data = array();

    $total_value = 0;

    foreach ( $search_results as $id => $data ) {
        $total_value += $data['value'];

        switch ( $analysis_by ) {
        case 'company':

            switch ( $analysis_display ) {
            case 'number':
                $tmp_data[$data['companyid']]['value']++;
                $tmp_data[$data['companyid']]['label'] = $gCompanies[$data['companyid']];
                $plottype = 'bars';
                break;

            case 'revenue':
                $tmp_data[$data['companyid']]['value'] += $data['value'];
                $tmp_data[$data['companyid']]['label'] = $gCompanies[$data['companyid']];
                $plottype = 'bars';
                break;

            case 'outcome':
                $tmp_data[$data['outcometype']+1]['value']++;

                switch ( $data['outcometype'] ) {
                case InnoworkOpportunity::OUTCOME_PENDING:
                    $tmp_data[$data['outcometype']+1]['label'] = $gLocale->getStr( 'pending.label' );
                    break;

                case InnoworkOpportunity::OUTCOME_POSITIVE:
                    $tmp_data[$data['outcometype']+1]['label'] = $gLocale->getStr( 'positive.label' );
                    break;

                case InnoworkOpportunity::OUTCOME_NEGATIVE:
                    $tmp_data[$data['outcometype']+1]['label'] = $gLocale->getStr( 'negative.label' );
                    break;
                }

                $plottype = 'pie';
                break;
            }

            break;
        }
    }

    $analysis_data = array();
    $legend = array();

    if ( $plottype == 'pie' ) $analysis_data[1][] = 0;

    foreach ( $tmp_data as $x => $data ) {
        if ( $plottype == 'bars' ) {
            $analysis_data[] = array( $data['label'], $data['value'] );
        } elseif ( $plottype == 'pie' ) {
            $analysis_data[1][] = $data['value'];
            $legend[] = $data['label'];
        }
    }

    reset( $search_results );

    $gXml_def =
'<vertgroup>
  <children>

    <grid>
      <children>

        <vertgroup row="0" col="0" halign="" valign="top">
          <children>

            <table>
              <args>
                <headers type="array">'.WuiXml::encode( $lists_headers ).'</headers>
                <width>100%</width>
              </args>
              <children>

                <vertgroup row="0" col="0">
                  <children>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'all_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'all'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'top_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'top'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'pending_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'pending'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'positive_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'positive'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'negative_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'negative'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'expired_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'expired'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <horizbar/>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'my_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'my'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'mytop_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'mytop'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <horizbar/>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'closed_opportunities.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'default',
                                    array(
                                        'opp_filter' => 'closed'
                                        )
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                <horizbar/>

                <link>
                  <args>
                    <label>'.( $gLocale->getStr( 'new_opportunity.label' ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'newopportunity'
                                    )
                                )
                            )
                        ).'</link>
                    <compact>true</compact>
                  </args>
                </link>

                    </children>
                  </vertgroup>

              </children>
            </table>

          </children>
        </vertgroup>

        <vertgroup row="0" col="1" halign="" valign="top">
          <children>

            <table>
              <name>opportunities</name>
              <args>
                <headers type="array">'.WuiXml::encode( $opp_headers ).'</headers>
                <width>100%</width>
                <rowsperpage>10</rowsperpage>
                <pagesactionfunction>opp_list_action_builder</pagesactionfunction>
                <pagenumber>'.( isset($eventData['pagenumber'] ) ? $eventData['pagenumber'] : '' ).'</pagenumber>
              </args>
              <children>';

    if ( count( $search_results ) ) {
        foreach ( $search_results as $id => $opportunity ) {
            $gXml_def .=
'<link row="'.$opps_row++.'" col="0">
  <args>
    <label>'.WuiXml::cdata( $gCompanies[$opportunity['companyid']].' - '.$opportunity['opportunity'].' - '.$locale_country->FormatMoney( $opportunity['value'] ) ).'</label>
                    <link>'.WuiXml::cdata(
                        WuiEventsCall::buildEventsCallString(
                            '',
                            array(
                                array(
                                    'view',
                                    'showopportunity',
                                    array(
                                        'id' => $id
                                        )
                                    )
                                )
                            )
                        ).'</link>
    <compact>true</compact>
  </args>
</link>';
        }
    } else {
        $gXml_def .=
'<label row="0" col="0">
  <args>
    <label>'.( $gLocale->getStr( 'no_opportunities.label' ) ).'</label>
  </args>
</label>';
    }

    $gXml_def .=
'              </children>
            </table>

          </children>
        </vertgroup>

      </children>
    </grid>

    <horizbar/>

    <table>
              <args>
                <headers type="array">'.WuiXml::encode( $analysis_headers ).'</headers>
                <width>100%</width>
              </args>
      <children>

        <vertgroup row="0" col="0">
          <children>

            <form><name>analysis</name>
              <args>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default',
                        array(
                            'set_analysis' => '1'
                            )
                        )
                    )
                )
            ).'</action>
              </args>
              <children>

                <grid>
                  <children>

                    <label row="0" col="0" halign="" valign="middle">
                      <args>
                        <label>'.( $gLocale->getStr( 'display.label' ) ).'</label>
                      </args>
                    </label>

                    <combobox row="0" col="1" halign="" valign="middle"><name>analysis_display</name>
                      <args>
                        <disp>view</disp>
                        <elements type="array">'.WuiXml::encode( $display_array ).'</elements>
                        <default>'.$analysis_display.'</default>
                      </args>
                    </combobox>

                    <!--
                    <label row="0" col="2" halign="" valign="middle">
                      <args>
                        <label>'.( $gLocale->getStr( 'by.label' ) ).'</label>
                      </args>
                    </label>

                    <combobox row="0" col="3" halign="" valign="middle"><name>analysis_by</name>
                      <args>
                        <disp>view</disp>
                        <elements type="array">'.WuiXml::encode( $by_array ).'</elements>
                        <default>'.$analysis_by.'</default>
                      </args>
                    </combobox>
                    -->

    <button row="0" col="4" halign="" valign="middle">
      <args>
        <themeimage>buttonok</themeimage>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'apply.button' ) ).'</label>
        <formsubmit>analysis</formsubmit>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default',
                        array(
                            'set_analysis' => '1'
                            )
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

                  </children>
                </grid>

              </children>
            </form>

          </children>
        </vertgroup>';

    if ( count( $search_results ) ) {
        $gXml_def .=
'<vertgroup row="1" col="0" halign="center">
  <children>
<phplot>
  <args>
    <width>550</width>
    <height>300</height>
    <plottype>'.$plottype.'</plottype>
    <data type="array">'.WuiXml::encode( $analysis_data ).'</data>
    '.( count( $legend ) ? '<legend type="array">'.WuiXml::encode( $legend ).'</legend>' : '' ).'
  </args>
</phplot>

<horizgroup>
  <children>

    <label>
      <args>
        <label>'.( $gLocale->getStr( 'total_value.label' ).' ' ).'</label>
        <bold>true</bold>
      </args>
    </label>

    <label>
      <args>
        <label>'.WuiXml::cdata( $locale_country->FormatMoney( $total_value ) ).'</label>
      </args>
    </label>

  </children>
</horizgroup>

  </children>
</vertgroup>';
    } else {
        $gXml_def .=
'<label row="0" col="0">
  <args>
    <label>'.( $gLocale->getStr( 'no_opportunities.label' ) ).'</label>
  </args>
</label>';
    }

    $gXml_def .=
'      </children>
    </table>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'newopportunity',
    'main_newopportunity'
    );
function main_newopportunity(
    $eventData
    )
{
    global $gXml_def, $gLocale, $gPage_title, $gCompanies;

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    $date_array = $locale_country->getDateArrayFromUnixTimestamp( time() );

    $row = 0;

    $gXml_def =
'
<vertgroup>
  <children>

    <form><name>opportunity</name>
      <args>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'showopportunity'
                        ),
                    array(
                        'action',
                        'addopportunity'
                        )
                    )
                )
            ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'company.label' ) ).'</label>
              </args>
            </label>

            <combobox row="'.$row++.'" col="0" halign="" valign="top"><name>companyid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $gCompanies ).'</elements>
              </args>
            </combobox>

            <label row="0" col="1" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'duedate.label' ) ).'</label>
              </args>
            </label>

            <date row="1" col="1" halign="" valign="top"><name>duedate</name>
              <args>
                <disp>action</disp>
                <type>date</type>
                <value type="array">'.WuiXml::encode( $date_array ).'</value>
              </args>
            </date>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'opportunity.label' ) ).'</label>
              </args>
            </label>

            <string row="'.$row++.'" col="0" halign="" valign="top"><name>opportunity</name>
              <args>
                <disp>action</disp>
                <size>60</size>
              </args>
            </string>

            <label row="2" col="1" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'value.label' ) ).'</label>
              </args>
            </label>

            <string row="3" col="1" halign="" valign="top"><name>value</name>
              <args>
                <disp>action</disp>
                <size>10</size>
              </args>
            </string>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'description.label' ) ).'</label>
              </args>
            </label>

            <text row="'.$row++.'" col="0" halign="" valign="top"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>10</rows>
                <cols>60</cols>
              </args>
            </text>

            <vertgroup row="5" col="1" halign="" valign="top">
              <children>
    <button>
      <args>
        <themeimage>kppp</themeimage>
        <themeimagetype>mini</themeimagetype>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'new_company.button' ) ).'</label>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                'innoworkdirectory',
                array(
                    array(
                        'view',
                        'newcompany'
                        )
                    )
                )
            ).'</action>
      </args>
</button>
              </children>
            </vertgroup>
          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <button>
      <args>
        <themeimage>buttonok</themeimage>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'apply.button' ) ).'</label>
        <formsubmit>opportunity</formsubmit>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'showopportunity'
                        ),
                    array(
                        'action',
                        'addopportunity'
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

  </children>
</vertgroup>';
}

$gMain_disp->addEvent(
    'showopportunity',
    'main_showopportunity'
    );
function main_showopportunity(
    $eventData
    )
{
    global $gXml_def, $gLocale, $gPage_title, $gCompanies;

    if ( isset($GLOBALS['innowork-opportunities']['newopportunityid'] ) ) {
        $eventData['id'] = $GLOBALS['innowork-opportunities']['newopportunityid'];
    }

    $opp = new InnoworkOpportunity(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
        $eventData['id']
        );

    $data = $opp->getItem();

    if ( $data['done'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue ) {
        $done_icon = 'buttonok';
        $done_label = $gLocale->getStr( 'setasundone.button' );
        $done_action = 'false';
    } else {
        $done_icon = 'redo';
        $done_label = $gLocale->getStr( 'setasdone.button' );
        $done_action = 'true';
    }

    $locale_country = new LocaleCountry(
        \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

    $date_array = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->getDateArrayFromTimestamp( $data['duedate'] );

    $row = 0;

    $gXml_def =
'
<horizgroup>
  <children>

<vertgroup>
  <children>

    <form><name>opportunity</name>
      <args>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default'
                        ),
                    array(
                        'action',
                        'editopportunity',
                        array(
                            'id' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
      <children>

        <grid>
          <children>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'company.label' ) ).'</label>
              </args>
            </label>

            <combobox row="'.$row++.'" col="0" halign="" valign="top"><name>companyid</name>
              <args>
                <disp>action</disp>
                <elements type="array">'.WuiXml::encode( $gCompanies ).'</elements>
                <default>'.$data['companyid'].'</default>
              </args>
            </combobox>

            <label row="0" col="1" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'duedate.label' ) ).'</label>
              </args>
            </label>

            <date row="1" col="1" halign="" valign="top"><name>duedate</name>
              <args>
                <disp>action</disp>
                <type>date</type>
                <value type="array">'.WuiXml::encode( $date_array ).'</value>
              </args>
            </date>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'opportunity.label' ) ).'</label>
              </args>
            </label>

            <string row="'.$row++.'" col="0" halign="" valign="top"><name>opportunity</name>
              <args>
                <disp>action</disp>
                <size>60</size>
                <value>'.( $data['opportunity'] ).'</value>
              </args>
            </string>

            <label row="2" col="1" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'value.label' ) ).'</label>
              </args>
            </label>

            <string row="3" col="1" halign="" valign="top"><name>value</name>
              <args>
                <disp>action</disp>
                <size>10</size>
                <value>'.WuiXml::cdata( $data['value'] ).'</value>
              </args>
            </string>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'description.label' ) ).'</label>
              </args>
            </label>

            <text row="'.$row++.'" col="0" halign="" valign="top"><name>description</name>
              <args>
                <disp>action</disp>
                <rows>10</rows>
                <cols>60</cols>
                <value>'.WuiXml::cdata( $data['description'] ).'</value>
              </args>
            </text>

            <vertgroup row="5" col="1" halign="" valign="top">
              <children>';

    require_once('innomatic/application/ApplicationDependencies.php');
    $app_dep = new ApplicationDependencies( \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess() );

    if ( $app_dep->IsEnabled( 'innowork-groupware', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() ) ) {
        $gXml_def .=
'<button>
      <args>
        <themeimage>kppp</themeimage>
        <themeimagetype>mini</themeimagetype>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'view_company.button' ) ).'</label>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                'innoworkdirectory',
                array(
                    array(
                        'view',
                        'showcompany',
                        array(
                            'id' => $data['companyid']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
</button>';
    }

    if ( $app_dep->IsEnabled( 'innowork-groupware', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() ) ) {
        $gXml_def .=
'<button>
      <args>
        <themeimage>todo</themeimage>
        <themeimagetype>mini</themeimagetype>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'create_todo.button' ) ).'</label>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                'innoworkactivities',
                array(
                    array(
                        'view',
                        'newactivity',
                        array(
                            'companyid' => $data['companyid'],
                            'opportunityid' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
</button>';
    }

    if ( $app_dep->IsEnabled( 'innowork-groupware', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId() ) ) {
        $gXml_def .=
'<button>
      <args>
        <themeimage>1day</themeimage>
        <themeimagetype>mini</themeimagetype>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'create_event.button' ) ).'</label>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                'innoworkcalendar',
                array(
                    array(
                        'view',
                        'newevent',
                        array(
                            'companyid' => $data['companyid']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
</button>';
    }

    $gXml_def .=
'              </children>
            </vertgroup>

            <label row="'.$row++.'" col="0" halign="" valign="top">
              <args>
                <label>'.( $gLocale->getStr( 'outcome.label' ) ).'</label>
              </args>
            </label>

            <text row="'.$row++.'" col="0" halign="" valign="top"><name>outcome</name>
              <args>
                <disp>action</disp>
                <rows>5</rows>
                <cols>60</cols>
                <value>'.WuiXml::cdata( $data['outcome'] ).'</value>
              </args>
            </text>

            <vertgroup row="7" col="1" halign="" valign="top">
              <children>

                <radio><name>outcometype</name>
                  <args>
                    <disp>action</disp>
                    <value>'.InnoworkOpportunity::OUTCOME_PENDING.'</value>
                    <label>'.( $gLocale->getStr( 'pending.label' ) ).'</label>
                    <checked>'.( $data['outcometype'] == InnoworkOpportunity::OUTCOME_PENDING ? 'true' : 'false' ).'</checked>
                  </args>
                </radio>

                <radio><name>outcometype</name>
                  <args>
                    <disp>action</disp>
                    <value>'.InnoworkOpportunity::OUTCOME_POSITIVE.'</value>
                    <label>'.( $gLocale->getStr( 'positive.label' ) ).'</label>
                    <checked>'.( $data['outcometype'] == InnoworkOpportunity::OUTCOME_POSITIVE ? 'true' : 'false' ).'</checked>
                  </args>
                </radio>

                <radio><name>outcometype</name>
                  <args>
                    <disp>action</disp>
                    <value>'.InnoworkOpportunity::OUTCOME_NEGATIVE.'</value>
                    <label>'.( $gLocale->getStr( 'negative.label' ) ).'</label>
                    <checked>'.( $data['outcometype'] == InnoworkOpportunity::OUTCOME_NEGATIVE ? 'true' : 'false' ).'</checked>
                  </args>
                </radio>

              </children>
            </vertgroup>

          </children>
        </grid>

      </children>
    </form>

    <horizbar/>

    <horizgroup>
      <children>

    <button>
      <args>
        <themeimage>buttonok</themeimage>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'apply.button' ) ).'</label>
        <formsubmit>opportunity</formsubmit>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default'
                        ),
                    array(
                        'action',
                        'editopportunity',
                        array(
                            'id' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

    <button>
      <args>
        <themeimage>fileclose</themeimage>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'close.button' ) ).'</label>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default'
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

    <button>
      <args>
        <themeimage>'.$done_icon.'</themeimage>
        <horiz>true</horiz>
        <label>'.( $done_label ).'</label>
        <formsubmit>opportunity</formsubmit>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default'
                        ),
                    array(
                        'action',
                        'editopportunity',
                        array(
                            'id' => $eventData['id'],
                            'done' => $done_action
                            )
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

    <button>
      <args>
        <themeimage>trash</themeimage>
        <horiz>true</horiz>
        <label>'.( $gLocale->getStr( 'trash.button' ) ).'</label>
        <needconfirm>true</needconfirm>
        <confirmmessage>'.( $gLocale->getStr( 'trash.confirm' ) ).'</confirmmessage>
        <action>'.WuiXml::cdata(
            WuiEventsCall::buildEventsCallString(
                '',
                array(
                    array(
                        'view',
                        'default'
                        ),
                    array(
                        'action',
                        'trashopportunity',
                        array(
                            'id' => $eventData['id']
                            )
                        )
                    )
                )
            ).'</action>
      </args>
    </button>

      </children>
    </horizgroup>

  </children>
</vertgroup>

  <innoworkitemacl><name>itemacl</name>
    <args>
      <itemtype>opportunity</itemtype>
      <itemid>'.$eventData['id'].'</itemid>
      <itemownerid>'.$data['ownerid'].'</itemownerid>
      <defaultaction>'.WuiXml::cdata( WuiEventsCall::buildEventsCallString( '', array(
        array( 'view', 'showopportunity', array( 'id' => $eventData['id'] ) ) ) ) ).'</defaultaction>
    </args>
  </innoworkitemacl>

  </children>
</horizgroup>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
    $gWui->addChild( new WuiInnomaticPage( 'page', array(
        'pagetitle' => $gPage_title,
        'icon' => 'moneydollar',
        'toolbars' => array(
            new WuiInnomaticToolbar(
                'view',
                array(
                    'toolbars' => $gToolbars, 'toolbar' => 'true'
                    ) ),
            new WuiInnomaticToolBar(
                'core',
                array(
                    'toolbars' => $gCore_toolbars, 'toolbar' => 'true'
                    ) )
                ),
        'maincontent' => new WuiXml(
            'page', array(
                'definition' => $gXml_def
                ) ),
        'status' => $gPage_status
        ) ) );

    $gWui->render();
