<?php

final class SprintProjectProfileController
  extends SprintProjectController {

  public function shouldAllowPublic() {
    return true;
  }

  public function handleRequest(AphrontRequest $request) {
    $viewer = $request->getViewer();

    $response = $this->loadProject();
    if ($response) {
      return $response;
    }

    $project = $this->getProject();
    $id = $project->getID();

    $picture = $project->getProfileImageURI();
    $icon = $project->getDisplayIconIcon();
    $icon_name = $project->getDisplayIconName();
    $tag = id(new PHUITagView())
        ->setIcon($icon)
        ->setName($icon_name)
        ->addClass('project-view-header-tag')
        ->setType(PHUITagView::TYPE_SHADE);

    $header = id(new PHUIHeaderView())
      ->setHeader(array($project->getDisplayName(), $tag))
      ->setViewer($viewer)
      ->setPolicyObject($project)
      ->setImage($picture)
      ->setProfileHeader(true);

    if ($project->getStatus() == PhabricatorProjectStatus::STATUS_ACTIVE) {
      $header->setStatus('fa-check', 'bluegrey', pht('Active'));
    } else {
      $header->setStatus('fa-ban', 'red', pht('Archived'));
    }

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $project,
      PhabricatorPolicyCapability::CAN_EDIT);

    if ($can_edit) {
      $header->setImageEditURL($this->getApplicationURI("picture/{$id}/"));
    }

    $properties = $this->buildPropertyListView($project);

    $watch_action = $this->renderWatchAction($project);
    $header->addActionLink($watch_action);

    $milestone_list = $this->buildMilestoneList($project);
    $subproject_list = $this->buildSubprojectList($project);

    $member_list = id(new PhabricatorProjectMemberListView())
      ->setViewer($viewer)
      ->setProject($project)
      ->setLimit(5)
      ->setBackground(PHUIBoxView::GREY)
      ->setUserPHIDs($project->getMemberPHIDs());

    $watcher_list = id(new PhabricatorProjectWatcherListView())
      ->setViewer($viewer)
      ->setProject($project)
      ->setLimit(5)
      ->setBackground(PHUIBoxView::GREY)
      ->setUserPHIDs($project->getWatcherPHIDs());

    $nav = $this->getProfileMenu();
    $nav->selectFilter(PhabricatorProject::PANEL_PROFILE);

    $stories = id(new PhabricatorFeedQuery())
      ->setViewer($viewer)
      ->setFilterPHIDs(
        array(
          $project->getPHID(),
        ))
      ->setLimit(50)
      ->execute();

    $feed = $this->renderStories($stories);
    $feed = phutil_tag_div('project-view-feed', $feed);

    $columns = id(new PHUITwoColumnView())
      ->setMainColumn(
        array(
          $properties,
          $feed,
        ))
      ->setSideColumn(
        array(
          $milestone_list,
          $subproject_list,
          $member_list,
          $watcher_list,
        ));

    $crumbs = $this->buildApplicationCrumbs();
    $crumbs->setBorder(true);

    require_celerity_resource('project-view-css');
    $home = phutil_tag(
      'div',
      array(
        'class' => 'project-view-home',
      ),
      array(
        $header,
        $columns,
      ));

    return $this->newPage()
      ->setNavigation($nav)
      ->setCrumbs($crumbs)
      ->setTitle($project->getDisplayName())
      ->setPageObjectPHIDs(array($project->getPHID()))
      ->appendChild(
        array(
          $home,
        ));
  }

  private function buildPropertyListView(
    PhabricatorProject $project) {
    $request = $this->getRequest();
    $viewer = $request->getViewer();

    $view = id(new PHUIPropertyListView())
      ->setViewer($viewer)
      ->setObject($project);

    $field_list = PhabricatorCustomField::getObjectFields(
      $project,
      PhabricatorCustomField::ROLE_VIEW);
    $field_list->appendFieldsToPropertyList($project, $viewer, $view);

    if (!$view->hasAnyProperties()) {
      return null;
    }

    $view = id(new PHUIBoxView())
      ->setColor(PHUIBoxView::GREY)
      ->appendChild($view)
      ->addClass('project-view-properties');

    return $view;
  }

  private function renderStories(array $stories) {
    assert_instances_of($stories, 'PhabricatorFeedStory');

    $builder = new PhabricatorFeedBuilder($stories);
    $builder->setUser($this->getRequest()->getUser());
    $builder->setShowHovercards(true);
    $view = $builder->buildView();

    return $view;
  }

  private function renderWatchAction(PhabricatorProject $project) {
    $viewer = $this->getViewer();
    $viewer_phid = $viewer->getPHID();
    $id = $project->getID();

    $is_watcher = ($viewer_phid && $project->isUserWatcher($viewer_phid));

    if (!$is_watcher) {
      $watch_icon = 'fa-eye';
      $watch_text = pht('Watch Project');
      $watch_href = "/project/watch/{$id}/?via=profile";
    } else {
      $watch_icon = 'fa-eye-slash';
      $watch_text = pht('Unwatch Project');
      $watch_href = "/project/unwatch/{$id}/?via=profile";
    }

    $watch_icon = id(new PHUIIconView())
      ->setIcon($watch_icon);

    return id(new PHUIButtonView())
      ->setTag('a')
      ->setWorkflow(true)
      ->setIcon($watch_icon)
      ->setText($watch_text)
      ->setHref($watch_href);
  }

  private function buildMilestoneList(PhabricatorProject $project) {
    if (!$project->getHasMilestones()) {
      return null;
    }

    $viewer = $this->getViewer();
    $id = $project->getID();

    $milestones = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withParentProjectPHIDs(array($project->getPHID()))
      ->needImages(true)
      ->withIsMilestone(true)
      ->withStatuses(
        array(
          PhabricatorProjectStatus::STATUS_ACTIVE,
        ))
      ->setOrder('newest')
      ->execute();
    if (!$milestones) {
      return null;
    }

    $milestone_list = id(new PhabricatorProjectListView())
      ->setViewer($viewer)
      ->setProjects($milestones)
      ->renderList();

    $view_all = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon(
        id(new PHUIIconView())
          ->setIcon('fa-list-ul'))
      ->setText(pht('View All'))
      ->setHref("/project/subprojects/{$id}/");

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Milestones'))
      ->addActionLink($view_all);

    return id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setBackground(PHUIBoxView::GREY)
      ->setObjectList($milestone_list);
  }

  private function buildSubprojectList(PhabricatorProject $project) {
    if (!$project->getHasSubprojects()) {
      return null;
    }

    $viewer = $this->getViewer();
    $id = $project->getID();

    $limit = 25;

    $subprojects = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withParentProjectPHIDs(array($project->getPHID()))
      ->needImages(true)
      ->withStatuses(
        array(
          PhabricatorProjectStatus::STATUS_ACTIVE,
        ))
      ->withIsMilestone(false)
      ->setLimit($limit)
      ->execute();
    if (!$subprojects) {
      return null;
    }

    $subproject_list = id(new PhabricatorProjectListView())
      ->setViewer($viewer)
      ->setProjects($subprojects)
      ->renderList();

    $view_all = id(new PHUIButtonView())
      ->setTag('a')
      ->setIcon(
        id(new PHUIIconView())
          ->setIcon('fa-list-ul'))
      ->setText(pht('View All'))
      ->setHref("/project/subprojects/{$id}/");

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Subprojects'))
      ->addActionLink($view_all);

    return id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->setBackground(PHUIBoxView::GREY)
      ->setObjectList($subproject_list);
  }

}
