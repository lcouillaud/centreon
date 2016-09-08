<?php
/*
 * Copyright 2005-2015 Centreon
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

if (!isset($centreon)) {
    exit();
}

include_once _CENTREON_PATH_."www/class/centreonGMT.class.php";
include_once _CENTREON_PATH_."www/class/centreonDB.class.php";
include_once _CENTREON_PATH_."www/class/centreonService.class.php";
include_once _CENTREON_PATH_."www/class/centreonHost.class.php";


/*
 * Init GMT class
 */
$centreonGMT = new CentreonGMT($pearDB);
$centreonGMT->getMyGMTFromSession(session_id(), $pearDB);
$hostStr = $centreon->user->access->getHostsString("ID", $pearDBO);

$hObj = new CentreonHost($pearDB);
$serviceObj = new CentreonService($pearDB);

if (!$centreon->user->access->checkAction("schedule_downtime")) {
    require_once("../errors/alt_error.php");
} else {
    /*
     * Init
     */
    $debug = 0;
    $attrsTextI = array("size" => "3");
    $attrsText = array("size" => "30");
    $attrsTextarea = array("rows" => "7", "cols" => "80");

    /*
     * Form begin
     */
    $form = new HTML_QuickForm('Form', 'POST', "?p=" . $p);

    /*
     * Indicator basic information
     */
    $redirect = $form->addElement('hidden', 'o');
    $redirect->setValue($o);

    if (isset($_GET["host_id"]) && !isset($_GET["service_id"])) {
        $disabled = "disabled";
        $host_name = $hObj->getHostName($_GET['host_id']);
    } elseif (isset($_GET["host_id"]) && isset($_GET["service_id"])) {
        $disabled = "disabled";
        $serviceParameters = $serviceObj->getParameters($_GET['service_id'], array('service_description'));
        $serviceDisplayName = $serviceParameters['service_description'];
        $host_name = $hObj->getHostName($_GET['host_id']);
    } else {
        $disabled = " ";
    }

    if (!isset($_GET['host_id'])) {

        $dtType[] = HTML_QuickForm::createElement('radio', 'downtimeType', null, _("Host"), '1',
            array($disabled, 'id' => 'host', 'onclick' => "toggleParams('host');"));
        $dtType[] = HTML_QuickForm::createElement('radio', 'downtimeType', null, _("Services"), '2',
            array($disabled, 'id' => 'service', 'onclick' => "toggleParams('service');"));
        $dtType[] = HTML_QuickForm::createElement('radio', 'downtimeType', null, _("Hostgroup"), '0',
            array($disabled, 'id' => 'hostgroup', 'onclick' => "toggleParams('hostgroup');"));
        $dtType[] = HTML_QuickForm::createElement('radio', 'downtimeType', null, _("Servicegroup"), '3',
            array($disabled, 'id' => 'servicegroup', 'onclick' => "toggleParams('servicegroup');"));
        $dtType[] = HTML_QuickForm::createElement('radio', 'downtimeType', null, _("Poller"), '4',
            array($disabled, 'id' => 'poller', 'onclick' => "toggleParams('poller');"));
        $form->addGroup($dtType, 'downtimeType', _("Downtime type"), '&nbsp;');

        // uncomment this section : the user can choose to set a downtime based on the host time or the centreon user time.
        /*
        $host_or_centreon_time[] = HTML_QuickForm::createElement('radio', 'host_or_centreon_time', null, _("Centreon Time"), '0');
        $host_or_centreon_time[] = HTML_QuickForm::createElement('radio', 'host_or_centreon_time', null, _("Host Time"), '1');
        $form->addGroup($host_or_centreon_time, 'host_or_centreon_time', _("Select Host or Centreon Time"), '&nbsp;');
        $form->setDefaults(array('host_or_centreon_time' => '0'));
        */

        /* ----- Hosts ----- */
        $attrHosts = array(
            'datasourceOrigin' => 'ajax',
            'availableDatasetRoute' => './api/internal.php?object=centreon_configuration_host&action=list',
            'multiple' => true,
            'linkedObject' => 'centreonHost'
        );
        $form->addElement('select2', 'host_id', _("Hosts"), array(), $attrHosts);

        if (!isset($_GET['service_id'])) {
            /* ----- Services ----- */
            $attrServices = array(
                'datasourceOrigin' => 'ajax',
                'availableDatasetRoute' => './api/internal.php?object=centreon_configuration_service&action=list',
                'multiple' => true,
                'linkedObject' => 'centreonService'
            );
            $form->addElement('select2', 'service_id', _("Services"), array($disabled), $attrServices);
        }

        /* ----- HostGroups ----- */
        $attrHostgroups = array(
            'datasourceOrigin' => 'ajax',
            'availableDatasetRoute' => './api/internal.php?object=centreon_configuration_hostgroup&action=list',
            'multiple' => true,
            'linkedObject' => 'centreonHostgroups'
        );
        $form->addElement('select2', 'hostgroup_id', _("Hostgroups"), array(), $attrHostgroups);

        /* ----- Servicegroups ----- */
        $attrServicegroups = array(
            'datasourceOrigin' => 'ajax',
            'availableDatasetRoute' => './api/internal.php?object=centreon_configuration_servicegroup&action=list',
            'multiple' => true,
            'linkedObject' => 'centreonServicegroups'
        );
        $form->addElement('select2', 'servicegroup_id', _("Servicegroups"), array(), $attrServicegroups);
    }

    /* ----- Pollers ----- */
    $attrPoller = array(
        'datasourceOrigin' => 'ajax',
        'allowClear' => false,
        'availableDatasetRoute' => './api/internal.php?object=centreon_configuration_poller&action=list',
        'multiple' => true,
        'linkedObject' => 'centreonInstance'
    );
    /* Host Parents */
    $attrPoller1 = array_merge(
        $attrPoller,
        array('defaultDatasetRoute' => './api/internal.php?object=centreon_configuration_poller&action=defaultValues&target=resources&field=instance_id&id='.$resource_id)
    );

    $form->addElement('select2', 'poller_id', _("Pollers"), array(), $attrPoller1);
    
    $chbx = $form->addElement('checkbox', 'persistant', _("Fixed"), null, array('id' => 'fixed', 'onClick' => 'javascript:setDurationField()'));
    if (isset($centreon->optGen['monitoring_dwt_fixed']) && $centreon->optGen['monitoring_dwt_fixed']) {
        $chbx->setChecked(true);
    }
    $form->addElement('text', 'start', _("Start Time"), array('size' => 10, 'class' => 'datepicker'));
    $form->addElement('text', 'end', _("End Time"), array('size' => 10, 'class' => 'datepicker'));
    $form->addElement('text', 'start_time', '', array('size' => 5, 'class' => 'timepicker'));
    $form->addElement('text', 'end_time', '', array('size' => 5, 'class' => 'timepicker'));
    $form->addElement('text', 'duration', _("Duration"), array('size' => '15', 'id' => 'duration'));
    $form->addElement('text','timezone_warning', _("*The timezone used is configured on your user settings"));

    $defaultDuration = 3600;    
    if (isset($centreon->optGen['monitoring_dwt_duration']) && $centreon->optGen['monitoring_dwt_duration']) {
        $defaultDuration = $centreon->optGen['monitoring_dwt_duration'];
    }
    $form->setDefaults(array('duration' => $defaultDuration));
    
    $form->addElement('select', 'duration_scale', _("Scale of time"), array("s" => _("seconds"), "m" => _("minutes"), "h" => _("hours"), "d" => _("days")));
    $defaultScale = 's';
    if (isset($centreon->optGen['monitoring_dwt_duration_scale']) && $centreon->optGen['monitoring_dwt_duration_scale']) {
        $defaultScale = $centreon->optGen['monitoring_dwt_duration_scale'];
    }
    $form->setDefaults(array('duration_scale' => $defaultScale));

    $with_services[] = HTML_QuickForm::createElement('radio', 'with_services', null, _("Yes"), '1');
    $with_services[] = HTML_QuickForm::createElement('radio', 'with_services', null, _("No"), '0');
    $form->addGroup($with_services, 'with_services', _("Set downtime for hosts services"), '&nbsp;');

    $form->addElement('textarea', 'comment', _("Comments"), $attrsTextarea);

    $form->addRule('end', _("Required Field"), 'required');
    $form->addRule('start', _("Required Field"), 'required');
    $form->addRule('end_time', _("Required Field"), 'required');
    $form->addRule('start_time', _("Required Field"), 'required');
    $form->addRule('comment', _("Required Field"), 'required');

    $data = array();
    $data["start"] = $centreonGMT->getDate("Y/m/d", time());
    $data["end"] = $centreonGMT->getDate("Y/m/d", time() + 7200);
    $data["start_time"] = $centreonGMT->getDate("G:i", time());
    $data["end_time"] = $centreonGMT->getDate("G:i", time() + 7200);
    $data["host_or_hg"] = 1;
    $data["with_services"] = $centreon->optGen['monitoring_dwt_svc'];

    if (isset($_GET["host_id"]) && !isset($_GET["service_id"])) {
        $data["host_id"] = $_GET["host_id"];
        $data["downtimeType"] = 1;
        $focus = 'host';
        $form->addElement('hidden', 'host_id', $_GET['host_id']);
        $form->addElement('hidden', 'downtimeType[downtimeType]', $data["downtimeType"]);
    } elseif (isset($_GET["host_id"]) && isset($_GET["service_id"])) {
        $data["service_id"] = $_GET["host_id"].'-'.$_GET["service_id"];
        $data["downtimeType"] = 2;
        $focus = 'service';
        $form->addElement('hidden', 'service_id',  $data["service_id"]);
        $form->addElement('hidden', 'downtimeType[downtimeType]', $data["downtimeType"]);
    } else {
        $data["downtimeType"] = 1;
        $focus = 'host';
    }

    $form->setDefaults($data);
    $subA = $form->addElement('submit', 'submitA', _("Save"));
    $res = $form->addElement('reset', 'reset', _("Reset"));

    /* Push the downtime */
    if ((isset($_POST["submitA"]) && $_POST["submitA"]) && $form->validate()) {
        $values = $form->getSubmitValues();

        if (!isset($_POST["persistant"]) || !in_array($_POST["persistant"], array('0', '1'))) {
            $_POST["persistant"] = '0';
        }
        if (!isset($_POST["comment"])) {
            $_POST["comment"] = 0;
        }

        $_POST["comment"] = str_replace("'", " ", $_POST['comment']);
        $duration = null;
        if (isset($_POST['duration'])) {
            if (isset($_POST['duration_scale'])) {
                $duration_scale = $_POST['duration_scale'];
            } else {
                $duration_scale = 's';
            }
                
            switch ($duration_scale) {
                default:
                case 's':
                    $duration = $_POST['duration'];
                    break;
                case 'm':
                    $duration = $_POST['duration'] * 60;
                    break;
                case 'h':
                    $duration = $_POST['duration'] * 60 * 60;
                    break;
                case 'd':
                    $duration = $_POST['duration'] * 60 * 60 * 24;
                    break;
            }
        }

        if (isset($_POST['host_or_centreon_time']['host_or_centreon_time']) && $_POST['host_or_centreon_time']['host_or_centreon_time']) {
            $host_or_centreon_time = $_POST['host_or_centreon_time']['host_or_centreon_time'];
        } else {
            $host_or_centreon_time = "0";
        }
            
        $dt_w_services = false;
        if ($values['with_services']['with_services'] == 1) {
            $dt_w_services = true;
        }

        if ($values['downtimeType']['downtimeType'] == 1) {
            /*
             * Set a downtime for only host
             */

            //catch fix input host_id
            if(!is_array($_POST["host_id"])){
                $_POST["host_id"] = array($_POST["host_id"]);
            }

            foreach ($_POST["host_id"] as $host_id) {
                $ecObj->addHostDowntime(
                    $host_id,
                    $_POST["comment"],
                    $_POST["start"].' '.$_POST['start_time'],
                    $_POST["end"].' '.$_POST['end_time'],
                    $_POST["persistant"],
                    $duration,
                    $dt_w_services,
                    $host_or_centreon_time
                );
            }
        } elseif ($values['downtimeType']['downtimeType'] == 0) {
            /*
             * Set a downtime for hostgroup
             */
            $hg = new CentreonHostgroups($pearDB);
            foreach ($_POST['hostgroup_id'] as $hg_id) {
                $hostlist = $hg->getHostGroupHosts($hg_id);
                $host_acl_id = preg_split('/,/', str_replace("'", "", $hostStr));
                foreach ($hostlist as $host_id) {
                    if ($centreon->user->access->admin || in_array($host_id, $host_acl_id)) {
                        $ecObj->addHostDowntime(
                            $host_id,
                            $_POST["comment"],
                            $_POST["start"] . ' '. $_POST["start_time"],
                            $_POST["end"] . ' ' . $_POST["end_time"],
                            $_POST["persistant"],
                            $duration,
                            $dt_w_services,
                            $host_or_centreon_time
                        );
                    }
                }
            }
        } elseif ($values['downtimeType']['downtimeType'] == 2) {
            /*
             * Set a downtime for a service list
             */

            //catch fix input service_id
            if(!is_array($_POST["service_id"])){
                $_POST["service_id"] = array($_POST["service_id"]);
            }

            foreach ($_POST["service_id"] as $value) {
                $info = split('-', $value);
                if ($centreon->user->access->admin || in_array($info[0], $host_acl_id)) {
                    $ecObj->addSvcDowntime(
                        $info[0],
                        $info[1],
                        $_POST["comment"],
                        $_POST["start"] . ' ' . $_POST['start_time'],
                        $_POST["end"] . ' ' . $_POST['end_time'],
                        $_POST["persistant"],
                        $duration,
                        $host_or_centreon_time
                    );
                }
            }
        } elseif ($values['downtimeType']['downtimeType'] == 3) {
            /*
             * Set a downtime for a service group list
             */
            foreach ($_POST["servicegroup_id"] as $sg_id) {
                $query = "SELECT host_id, service_id FROM services_servicegroups WHERE servicegroup_id = $sg_id";
                $DBRESULT = $pearDBO->query($query);
                while ($row = $DBRESULT->fetchRow()) {
                    $ecObj->addSvcDowntime(
                        $row["host_id"],
                        $row["service_id"],
                        $_POST["comment"],
                        $_POST["start"] . ' ' . $_POST['start_time'],
                        $_POST["end"] . ' ' . $_POST['end_time'],
                        $_POST["persistant"],
                        $duration,
                        $host_or_centreon_time
                    );
                }
            }
            
       } elseif ($values['downtimeType']['downtimeType'] == 4) {
            /*
             * Set a downtime for poller
             */
            foreach ($_POST['poller_id'] as $poller_id) {
                
                $host_id = preg_split('/,/', str_replace("'", "", $hostStr));

                $DBRESULT = $pearDBO->query("SELECT host_id FROM hosts WHERE instance_id = $poller_id AND enabled = 1");
                while ($row = $DBRESULT->fetchRow()) {
                    if ($centreon->user->access->admin || isset($host_acl_id[$host_id])) {
                        $ecObj->addHostDowntime(
                            $row['host_id'],
                            $_POST["comment"],
                            $_POST["start"] . ' '. $_POST["start_time"],
                            $_POST["end"] . ' ' . $_POST["end_time"],
                            $_POST["persistant"],
                            $duration,
                            $dt_w_services,
                            $host_or_centreon_time
                        );
                    }
                }
            }
        }
        require_once("listDowntime.php");
    } else {
        /*
         * Smarty template Init
         */
        $tpl = new Smarty();
        $tpl = initSmartyTpl($path, $tpl, "template/");

        /*
         * Apply a template definition
         */
        $renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
        $renderer->setRequiredTemplate('{$label}&nbsp;<font color="red" size="1">*</font>');
        $renderer->setErrorTemplate('<font color="red">{$error}</font><br />{$html}');
        $form->accept($renderer);
        $tpl->assign('form', $renderer->toArray());

        if(isset($_GET['service_id']) && isset($_GET['host_id'])){
            $tpl->assign('host_name', $host_name);
            $tpl->assign('service_description', $serviceDisplayName);
        }elseif (isset($_GET['host_id'])){
            $tpl->assign('host_name', $host_name);
        }

        $tpl->assign('seconds', _("seconds"));
        $tpl->assign('o', $o);
        $tpl->assign('focus', $focus);
        $tpl->display("AddDowntime.ihtml");
    }
}
?>
<script type='text/javascript'>

jQuery(function() {
    setDurationField();
    
    <?php 
    if (isset($data["service_id"])) {
        print "toggleParams('service');";
    }
    ?>
    
});

function setDurationField() {
    var durationField = document.getElementById('duration');
    var fixedCb = document.getElementById('fixed');

    if (fixedCb.checked == true) {
        durationField.disabled = true;
    } else {
        durationField.disabled = false;
    }
}
</script>