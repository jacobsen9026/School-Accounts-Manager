<?php

/*
 * The MIT License
 *
 * Copyright 2020 cjacobsen.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace App\Controllers;

/**
 * Description of Groups
 *
 * @author cjacobsen
 */

use App\Api\Ad\ADConnection;
use App\Models\Audit\Action\Group\AddMemberAuditAction;
use App\Models\Audit\Action\Group\CreateGroupAuditAction;
use App\Models\Audit\Action\Group\DeleteGroupAuditAction;
use App\Models\Audit\Action\Group\RemoveMemberAuditAction;
use App\Models\Audit\Action\Group\SearchGroupAuditAction;
use App\Models\Domain\DomainGroup;
use App\Models\Domain\DomainUser;
use System\App\AppException;
use System\App\LDAPLogger;
use System\Lang;
use System\Post;
use System\Request;

class Groups extends Controller
{

    public function createPost()
    {
        if ($this->user->superAdmin) {

            $name = Post::get("name");
            $desc = Post::get("description");
            $email = Post::get("email");
            $ou = Post::get("ou");
            if ($name != null and $ou != null) {

                $newGroup = ADConnection::get()->getDefaultProvider()->make()->group()
                    ->setName($name)
                    ->setAccountName($name)
                    ->setAttribute('description', $desc)
                    ->setAttribute('mail', $email)
                    ->setDistinguishedName('CN=' . $name . ',' . $ou);
                LDAPLogger::get()->info("New group ready");
                LDAPLogger::get()->info($newGroup);
                $newGroup->save();
                $group = new DomainGroup($newGroup->getAccountName());
                $this->audit(new CreateGroupAuditAction($group));
                $this->redirect('/groups/search/' . $name);

            }
        }
    }

    /**
     * Search but by post
     *
     * @param string $groupName
     *
     * @return type
     */
    public function searchPost($groupName = null)
    {
        $group = Post::get("group");
        if (!is_null($groupName)) {
            return $this->search($groupName);
        } else {
            /**
             * If I want to not require the group in the url I can use this
             * for post only requests. Can check the $group variable
             */
        }
    }

    /**
     * Searches for groups by name and returns a display view
     *
     * @param string $groupName
     *
     * @return string
     */
    public function search(string $groupName = null)
    {
        if ($groupName == null) {
            return $this->index();
        } else {
            $this->group = new DomainGroup($groupName);
            $this->audit(new SearchGroupAuditAction($groupName));
            //var_dump($this->group);
            return $this->view('/groups/show');
        }

    }

    public function index()
    {
        $return = $this->view('/groups/search');
        //$return .= $this->view('/groups/create');


        return $return;
    }

    /**
     * Handles all group changes by Post
     *
     * @return type
     * @throws AppException
     * @throws \System\CoreException
     */
    public function editPost()
    {
        $action = Post::get('action');
        $groupName = Post::get("group");
        $username = Post::get('username');
        $distinguishedName = Post::get('distinguishedName');
        switch ($action) {
            case 'removeMember':
                $this->logger->info("removing member " . $distinguishedName);
                $group = new DomainGroup($groupName);
                $user = $group->hasMember($distinguishedName);

                $this->logger->debug($user);
                if ($user !== false) {
                    if ($group->activeDirectory->removeMember($user->activeDirectory)) {

                        $this->audit(new RemoveMemberAuditAction($groupName, $user->getUsername()));
                        $this->logger->debug("user was successfully removed");
                    } else {
                        throw new AppException('Could not remove member from group');
                    }
                }
                $this->logger->debug($group);

                break;
            case 'addMember':
                $group = new DomainGroup($groupName);
                $username = Post::get('username');

                $this->logger->info("adding member " . $username);
                $user = null;
                try {

                    $user = new DomainUser($username);
                } catch (AppException $ex) {
                    $this->logger->error($ex->getMessage());
                    if ($ex->getCode() == AppException::USER_NOT_FOUND) {
                        $this->logger->debug("Searching by group name");
                        $user = new DomainGroup($username);
                    }
                }

                if (!is_null($user) && $group->addMember($user->getDistinguishedName())) {
                    $this->audit(new AddMemberAuditAction($groupName, $username));
                    $this->logger->debug("user was successfully removed");
                } else {
                    throw new AppException(Lang::getError("Object not found"), AppException::OBJECT_NOT_FOUND);

                }


                break;
            default:
                break;


        }
        $this->redirect(Request::get()->getReferer());
        if (strpos(Request::get()->getReferer(), "groups") !== false) {
            //return $this->search($group->activeDirectory->getName());
        } else {
            //$this->redirect('/users/search/' . $user->getUsername());
            //$users = new Users($this->app);
            //return $users->search($user->getUsername());
        }

    }

    public function deletePost()
    {
        $groupDN = Post::get("groupDN");
        $group = new DomainGroup ($groupDN);
        $group->delete();

        $this->audit(new DeleteGroupAuditAction($groupDN));
        $this->redirect('/groups');
    }

}
