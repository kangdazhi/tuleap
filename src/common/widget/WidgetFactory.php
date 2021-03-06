<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Widget;

use UserManager;
use User_ForgeUserGroupPermissionsManager;
use User_ForgeUserGroupPermission_ProjectApproval;
use EventManager;
use Widget;
use Widget_Contacts;
use Widget_MyAdmin;
use Widget_MyImageViewer;
use Widget_MyProjects;
use Widget_MyBookmarks;
use Widget_MyMonitoredForums;
use Widget_MyMonitoredFp;
use Widget_MyLatestSvnCommits;
use Widget_MyArtifacts;
use Widget_MyRss;
use Widget_MySystemEvent;
use Widget_ProjectClassification;
use Widget_ProjectDescription;
use Widget_ProjectImageViewer;
use Widget_ProjectLatestCvsCommits;
use Widget_ProjectLatestFileReleases;
use Widget_ProjectLatestNews;
use Widget_ProjectLatestSvnCommits;
use Widget_ProjectMembers;
use Widget_ProjectPublicAreas;
use Widget_ProjectRss;
use Widget_ProjectSvnStats;

class WidgetFactory
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $forge_ugroup_permissions_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        UserManager $user_manager,
        User_ForgeUserGroupPermissionsManager $forge_ugroup_permissions_manager,
        EventManager $event_manager
    ) {
        $this->user_manager                     = $user_manager;
        $this->forge_ugroup_permissions_manager = $forge_ugroup_permissions_manager;
        $this->event_manager                    = $event_manager;
    }

    /**
     * @return Widget
     */
    public function getInstanceByWidgetName($widget_name)
    {
        $widget             = null;
        $user               = $this->user_manager->getCurrentUser();
        $user_is_super_user = $user->isSuperUser();

        switch ($widget_name) {
            case 'myprojects':
                $widget = new Widget_MyProjects();
                break;
            case 'mybookmarks':
                $widget = new Widget_MyBookmarks();
                break;
            case 'mymonitoredforums':
                $widget = new Widget_MyMonitoredForums();
                break;
            case 'mymonitoredfp':
                $widget = new Widget_MyMonitoredFp();
                break;
            case 'mylatestsvncommits':
                $widget = new Widget_MyLatestSvnCommits();
                break;
            case 'myartifacts':
                $widget = new Widget_MyArtifacts();
                break;
            case 'myrss':
                $widget = new Widget_MyRss();
                break;
            case 'myimageviewer':
                $widget = new Widget_MyImageViewer();
                break;
            case 'myadmin':
                if (! $user_is_super_user) {
                    $can_access = $this->forge_ugroup_permissions_manager->doesUserHavePermission(
                        $user,
                        new User_ForgeUserGroupPermission_ProjectApproval()
                    );
                }

                if ($user_is_super_user || $can_access) {
                    $widget = new Widget_MyAdmin($user_is_super_user);
                }
                break;
            case 'mysystemevent':
                if ($user_is_super_user) {
                    $widget = new Widget_MySystemEvent();
                }
                break;
            case 'projectdescription':
                $widget = new Widget_ProjectDescription();
                break;
            case ProjectHeartbeat::NAME:
                $widget = new ProjectHeartbeat();
                break;
            case 'projectclassification':
                $widget = new Widget_ProjectClassification();
                break;
            case 'projectmembers':
                $widget = new Widget_ProjectMembers();
                break;
            case 'projectlatestfilereleases':
                $widget = new Widget_ProjectLatestFileReleases();
                break;
            case 'projectlatestnews':
                $widget = new Widget_ProjectLatestNews();
                break;
            case 'projectpublicareas':
                $widget = new Widget_ProjectPublicAreas();
                break;
            case 'projectrss':
                $widget = new Widget_ProjectRss();
                break;
            case 'projectsvnstats':
                $widget = new Widget_ProjectSvnStats();
                break;
            case 'projectlatestsvncommits':
                $widget = new Widget_ProjectLatestSvnCommits();
                break;
            case 'projectlatestcvscommits':
                $widget = new Widget_ProjectLatestCvsCommits();
                break;
            case 'projectimageviewer':
                $widget = new Widget_ProjectImageViewer();
                break;
            case 'projectcontacts':
                $widget = new Widget_Contacts();
                break;
            default:
                $this->event_manager->processEvent(
                    'widget_instance',
                    array('widget' => $widget_name, 'instance' => &$widget)
                );
                break;
        }

        if (! $widget || ! is_a($widget, 'Widget')) {
            $widget = null;
        }

        return $widget;
    }
}
