<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class AgileDashboard_Milestone_Pane_ContentPresenterBuilder {

    /** @var AgileDashboard_BacklogItemDao */
    private $dao;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var String */
    private $parent_item_name = '';

    /** @var String */
    private $can_add_backlog_item = false;

    /** @var String */
    private $new_backlog_item_url = '';

    /** @var AgileDashboard_Milestone_Pane_BacklogRowCollectionFactory */
    private $collection_factory;

    public function __construct(
        AgileDashboard_BacklogItemDao $dao,
        Tracker_ArtifactFactory $artifact_factory,
        PlanningFactory $planning_factory,
        AgileDashboard_Milestone_Pane_BacklogRowCollectionFactory $collection_factory
    ) {
        $this->dao                  = $dao;
        $this->artifact_factory     = $artifact_factory;
        $this->planning_factory     = $planning_factory;
        $this->collection_factory   = $collection_factory;
    }

    public function getMilestoneContentPresenter(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $redirect_paremeter     = new Planning_MilestoneRedirectParameter();
        $backlog_strategy       = $this->getBacklogStrategy($milestone);
        $this->redirect_to_self = $redirect_paremeter->getPlanningRedirectToSelf($milestone, AgileDashboard_Milestone_Pane_ContentPaneInfo::IDENTIFIER);

        $this->initBacklogSettings($user, $milestone);

        return new AgileDashboard_Milestone_Pane_ContentPresenter(
            $this->collection_factory->getTodoCollection($user, $milestone, $backlog_strategy, $this->redirect_to_self),
            $this->collection_factory->getDoneCollection($user, $milestone, $backlog_strategy, $this->redirect_to_self),
            $this->parent_item_name,
            $backlog_strategy->getItemName(),
            $this->can_add_backlog_item,
            $this->new_backlog_item_url
        );
    }

    private function initBacklogSettings(PFUser $user, Planning_ArtifactMilestone $milestone) {
        $backlog_tracker = $milestone->getPlanning()->getBacklogTracker();
        if ($backlog_tracker->userCanSubmitArtifact($user)) {
            $this->can_add_backlog_item = true;
        }

        $this->new_backlog_item_url = $milestone->getArtifact()->getSubmitNewArtifactLinkedToMeUri($backlog_tracker).'&'.$this->redirect_to_self;
    }

    /**
     * @return AgileDashboard_Milestone_Pane_BacklogStrategy
     */
    private function getBacklogStrategy(Planning_ArtifactMilestone $milestone) {
        $milestone_backlog_artifacts = $this->getBacklogArtifacts($milestone);
        $backlog_tracker_children    = $milestone->getPlanning()->getPlanningTracker()->getChildren();
        $backlog_tracker             = $milestone->getPlanning()->getBacklogTracker();

        if ($backlog_tracker_children) {
            $first_child_tracker  = current($backlog_tracker_children);
            $first_child_planning = $this->planning_factory->getPlanningByPlanningTracker($first_child_tracker);
            if ($first_child_planning) {
                $first_child_backlog_tracker = $first_child_planning->getBacklogTracker();

                if ($first_child_backlog_tracker != $backlog_tracker) {
                    return new AgileDashboard_Milestone_Pane_DescendantBacklogStrategy(
                        $milestone_backlog_artifacts,
                        $first_child_backlog_tracker->getName(),
                        $this->dao
                    );
                }
            }
        }

        return new AgileDashboard_Milestone_Pane_SelfBacklogStrategy(
            $milestone_backlog_artifacts,
            $backlog_tracker->getName()
        );
    }

    private function getBacklogArtifacts(Planning_ArtifactMilestone $milestone) {
        return $this->dao->getBacklogArtifacts($milestone->getArtifactId())->instanciateWith(array($this->artifact_factory, 'getInstanceFromRow'));
    }
}
?>
