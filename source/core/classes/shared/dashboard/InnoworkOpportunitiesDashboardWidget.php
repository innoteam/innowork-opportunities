<?php

namespace Shared\Dashboard;

use \Innomatic\Core\InnomaticContainer;
use \Shared\Wui;

class InnoworkOpportunitiesDashboardWidget extends \Innomatic\Desktop\Dashboard\DashboardWidget
{
    public function getWidgetXml()
    {
        require_once('innowork/opportunities/InnoworkOpportunity.php');

        $locale_catalog = new \Innomatic\Locale\LocaleCatalog(
                'innowork-opportunities::domain_main',
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage()
        );

        $locale_country = new \Innomatic\Locale\LocaleCountry(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry()
        );

        $opp = new InnoworkOpportunity(
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

        // Define search parameters

        $search_keys = array(
            'done' => \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse
            );

        $restrict_to = InnoworkItem::SEARCH_RESTRICT_TO_OWNER;
        $opp->mSearchOrderBy = 'value DESC';
        $limit = 10;

        $xml = '<label><args><label>Opportunities</label></args></label>';

        // Execute search

        $search_results = $opp->Search(
                $search_keys,
                \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId(),
                false,
                false,
                $limit,
                0,
                $restrict_to
        );

        /*
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
        */

        // Analysis

        $analysis_headers[0]['label'] = $locale_catalog->getStr( 'analysis.header' );

        $display_array['number'] = $locale_catalog->getStr( 'display_opportunities_number.label' );
        $display_array['revenue'] = $locale_catalog->getStr( 'display_opportunities_revenue.label' );
        $display_array['outcome'] = $locale_catalog->getStr( 'display_opportunities_outcome.label' );

        //$by_array['sector'] = $locale_catalog->getStr( 'by_sector.label' );
        $by_array['company'] = $locale_catalog->getStr( 'by_company.label' );

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
        $plottype = '';

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
                                    $tmp_data[$data['outcometype']+1]['label'] = $locale_catalog->getStr( 'pending.label' );
                                    break;

                                case InnoworkOpportunity::OUTCOME_POSITIVE:
                                    $tmp_data[$data['outcometype']+1]['label'] = $locale_catalog->getStr( 'positive.label' );
                                    break;

                                case InnoworkOpportunity::OUTCOME_NEGATIVE:
                                    $tmp_data[$data['outcometype']+1]['label'] = $locale_catalog->getStr( 'negative.label' );
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

        $xml =
        '<vertgroup row="1" col="0" halign="center">
  <children>
<phplot>
  <args>
    <width>'.$this->getDefaultWidth().'</width>
    <height>'.$this->getHeight().'</height>
    <plottype>'.$plottype.'</plottype>
    <data type="array">'.WuiXml::encode( $analysis_data ).'</data>
    '.( count( $legend ) ? '<legend type="array">'.WuiXml::encode( $legend ).'</legend>' : '' ).'
  </args>
</phplot>

<horizgroup>
  <children>

    <label>
      <args>
        <label>'.( $locale_catalog->getStr( 'total_value.label' ).' ' ).'</label>
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

        return $xml;
    }

    public function getWidth()
    {
        return 1;
    }

    public function getHeight()
    {
        return 300;
    }
}
