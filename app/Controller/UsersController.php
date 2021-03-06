<?php
App::uses('Sanitize', 'Utility');
class UsersController extends AppController {
	public $components = array('Cookie');
	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->allow('add', 'logout', 'change_password', 'remember_password', 'remember_password_step_2', 'view', 'opauth_complete', 'thanks', 'invite');

	}

	public function index() {
		if (AuthComponent::user('role') != 'admin') {
			throw new ForbiddenException("You're now allowed to do this.");
		}
		$this->User->recursive = 2;
		$this->layout = 'admin';
		$this->set('users', $this->paginate());
	}

	public function invite($inviter = null) {
		$this->User->recursive = 0;
		$conditions = array(
			'User.username' => $inviter,
		);
// if the user exists by email
		if ($this->User->hasAny($conditions)) {
			$this->Cookie->write('invited', 'true');
			$user = $this->User->find('first', array('conditions' => array('User.username' => $inviter), 'fields' => array('id', 'image', 'first_name', 'last_name')));

			$id = $user['User']['id'];
		} else {
			return $this->redirect('/');
		}

		$this->set(compact('user'));
		$this->layout = 'welcome';
	}

	public function thanks() {
                if ($this->request->is('post')) {
				$codes = array('CMX1515', 'ILoveCMGR');
				$code = $this->request->data['User']['secret_code'];

                        if (in_array($code, $codes, true)) {

				unset($this->request->data['User']['secret_code']);
				$this->request->data['User']['has_access'] = true;
				$user = $this->User->read(null, AuthComponent::user('id'));

				$this->set('user', $user);

				if ($this->User->saveField('has_access', true)) {
					$this->Session->write('Auth', $this->User->read(null, AuthComponent::user('id')));
					$this->Session->setFlash(__('Welcome!'), 'flash_success');
					return $this->redirect('/?code='.$code);
				}
			} else {
				$this->Session->setFlash(__('Thanks for signing up - the code was incorrect. We will be releasing CMGR to the public soon!'), 'flash_fail');
				return $this->redirect('/thanks');
			}
		}

		$this->layout = 'welcome';

		// if (AuthComponent::user('role') != 'admin') {
		// 	throw new ForbiddenException("You're now allowed to do this.");
		// }
		// $this->User->recursive = 2;
		// $this->layout = 'admin';
		// $this->set('users', $this->paginate());
	}

	public function opauth_complete() {
		$conditions = array(
			'User.username' => $this->data['auth']['uid'],
		);
// if the user exists by email
		if ($this->User->hasAny($conditions)) {
//debug($this->data);
			//log them in
			$user = $this->User->find('first', array('conditions' => array('User.username' => $this->data['auth']['uid'])));
			$id = $user['User']['id'];
			// if (isset($this->data['auth']['info']['image'])) {
			// 	copy($this->data['auth']['info']['image'], '../webroot/img/users/' . $id . '.jpg');
			// }
			$this->request->data['User'] = array_merge(
				$user['User'],
				array('id' => $id)
			);
			unset($this->request->data['User']['password']);
			if ($this->Cookie->read('invited')) {
				$user['User']['has_access'] = true;
				// $this->User->saveField('has_access', true);
				$this->User->updateAll(
					array('has_access' => true)
				);
			}

			if ($user['User']['has_access']) {
				$this->Auth->login($this->request->data['User']);
				// $this->User->saveField('last_login', date(DATE_ATOM));
				$this->User->id = $user['User']['id'];
				$now = date('Y-m-d H:i:s');
				$this->User->saveField('last_login', $now);
				return $this->redirect('/');
			} else {
				$this->Auth->login($this->request->data['User']);

				return $this->redirect('/thanks');
			}

		}
// if the user does not exist
		else {
			// create them
			if ($this->request->is('post')) {
				$this->request->data['User']['linkedin_id'] = $this->data['auth']['uid'];
				$this->request->data['User']['email'] = $this->data['auth']['info']['email'];
				if (isset($this->data['auth']['info']['image'])) {
					$this->request->data['User']['image'] = $this->data['auth']['info']['image'];
				}
				$this->request->data['User']['username'] = $this->data['auth']['uid'];
				$this->request->data['User']['first_name'] = $this->data['auth']['info']['first_name'];
				$this->request->data['User']['last_name'] = $this->data['auth']['info']['last_name'];
				$this->request->data['User']['headline'] = $this->data['auth']['info']['headline'];
				$this->request->data['User']['linkedin_link'] = $this->data['auth']['info']['urls']['linkedin'];

				$this->User->create();

				if ($this->User->save($this->request->data)) {
					$id = $this->User->id;
					// if (isset($this->data['auth']['info']['image'])) {
					// 	copy($this->data['auth']['info']['image'], '../webroot/img/users/' . $id . '.jpg');
					// }
					$this->User->id = $id;
				$now = date('Y-m-d H:i:s');
				$this->User->saveField('last_login', $now);

					$this->request->data['User'] = array_merge(
						$this->request->data['User'],
						array('id' => $id)
					);
					unset($this->request->data['User']['password']);
					$this->Auth->login($this->request->data['User']);
					return $this->redirect('/thanks');
				} else {
					# Create a loop with validation errors
					$this->Error->set($this->User->invalidFields());
				}
			}
		}

	}

	public function login() {

		if ($this->request->is('post')) {
			try {
				# Retrieve user username for auth
				$this->request->data['User']['username'] = $this->User->getUsername($this->request->data['User']['email']);
			} catch (Exception $e) {
				# In case that this email dont exists in database
				$this->Session->setFlash($e->getMessage(), 'flash_fail');
				$this->redirect('/');
			}

			# Try to log in the user
			if ($this->Auth->login()) {
				if (!empty($this->request->data['User']['remember_me']) && $this->request->data['User']['remember_me'] == 'S') {
					$cookie = array();
					$cookie['username'] = $this->request->data['User']['username'];
					$cookie['password'] = $this->Auth->password($this->request->data['User']['password']);

					# Write cookie ( 30 Days )
					$this->Cookie->write('Auth.User', $cookie, true);
				}

				# Redirect to home
				$this->redirect($this->Auth->redirectUrl());
			} else {
				// $this->Session->setFlash(__('Invalid username or password, try again'), 'flash_fail');
				$this->redirect('/users/login?pw=false');
			}
		}
	}

	public function logout() {
		# Destroy the Cookie
		$this->Cookie->delete('Auth.User');

		# Destroy the session
		$this->redirect($this->Auth->logout());
	}

	public function view($username = null) {
		// if (AuthComponent::user('role') != 'admin') {
		// 	throw new ForbiddenException("You're now allowed to do this.");
		// }
		$this->User->recursive = 2;
		$user = $this->User->findByUsername($username);
		$user = Hash::extract($user, 'User');
		$this->User->id = $user['id'];

		// if (!$this->User->exists()) {
		// 	throw new NotFoundException(__('Invalid user'));
		// }
		$itemoptions = array('conditions' => array('Item.user_id' => $user['id']), 'order' => array(
			'Item.created' => 'desc',
		));
		$commentoptions = array('conditions' => array('Comment.user_id' => $user['id']), 'order' => array(
			'Comment.created' => 'desc',
		));

		// $upvoteoptions = array('conditions' => array('Upvote.user_id' => $user['id']), 'order' => array(
		// 	'Upvote.id' => 'desc',
		// ));

		$this->set('user', $this->User->findByUsername($user['username']));
		$this->set('items', $this->User->Item->find('all', $itemoptions));
		$this->set('comments', $this->User->Comment->find('all', $commentoptions));
		// $this->set('upvotes', $this->User->Item->Upvote->find('all', $upvoteoptions));
		$this->layout = 'default';

	}

	public function add() {
		if ($this->request->is('post')) {
			$this->User->create();

			if ($this->User->save($this->request->data)) {
				if (AuthComponent::user('id')) {
					# Store log
					CakeLog::info('The user ' . AuthComponent::user('username') . ' (ID: ' . AuthComponent::user('id') . ') registered user (ID: ' . $this->User->id . ')', 'users');
				}
				$this->Session->setFlash(__('The user has been saved'), 'flash_success');
				$this->redirect(array('action' => 'index'));
			} else {
				# Create a loop with validation errors
				$this->Error->set($this->User->invalidFields());
			}
		}
		$this->set('label', 'Register user');
		$this->render('_form');
	}

	public function edit($id = null) {

		# If its not an admin, he will edit his own profile only
		if (AuthComponent::user('role') != 'admin' || empty($id)) {
			$id = AuthComponent::user('id');
			$this->set('user', AuthComponent::user());
		} else {
			$this->User->id = $id;

			if (!$this->User->exists()) {
				throw new NotFoundException(__('Invalid user'));
			}
			$this->set('user', $user = Hash::extract($this->User->findById($id), 'User'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
			if (empty($this->request->data['User']['password'])) {
				unset($this->request->data['User']['password']);
			}

			if ($this->User->save($this->request->data)) {
				# Store log
				CakeLog::info('The user ' . AuthComponent::user('username') . ' (ID: ' . AuthComponent::user('id') . ') edited user (ID: ' . $this->User->id . ')', 'users');

				$this->Session->setFlash(__('The user has been saved'), 'flash_success');
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The user could not be saved. Please, try again.'), 'flash_fail');
			}
		} else {
			$this->request->data = $this->User->read(null, $id);
			unset($this->request->data['User']['password']);
		}
		$this->set('label', 'Edit user');
		$this->render('_form');
	}

	public function grant($id = null) {
		if (AuthComponent::user('role') != 'admin') {
			throw new ForbiddenException("You're not allowed to do this.");
		}
		$this->User->id = $id;

		if ($this->User->saveField('has_access', true)) {
			return $this->redirect('/usersAdmin/index');
		} else {
			return $this->redirect('/');
		}

	}

	public function revoke($id = null) {
		if (AuthComponent::user('role') != 'admin') {
			throw new ForbiddenException("You're not allowed to do this.");
		}
		$this->User->id = $id;

		if ($this->User->saveField('has_access', false)) {
			return $this->redirect('/usersAdmin/index');
		} else {
			return $this->redirect('/');
		}

	}

	public function delete($id = null) {
		if (AuthComponent::user('role') != 'admin') {
			throw new ForbiddenException("You're now allowed to do this.");
		}

		$this->User->id = $id;

		if (!$this->User->exists()) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ($this->User->delete()) {
			# Store log
			CakeLog::info('The user ' . AuthComponent::user('username') . ' (ID: ' . AuthComponent::user('id') . ') deleted user (ID: ' . $this->User->id . ')', 'users');

			$this->Session->setFlash(__('User deleted'), 'flash_success');
			$this->redirect(array('controller' => 'usersadmin', 'action' => 'index'));
		}

		$this->Session->setFlash(__('User was not deleted'), 'flash_fail');

		$this->redirect(array('action' => 'index'));
	}

	public function change_password() {
		$user = $this->User->read(null, AuthComponent::user('id'));
		$this->set('user', $user);

		if ($this->request->is('post')) {
			# Verify if password matches
			if ($this->request->data['User']['password'] === $this->request->data['User']['re_password']) {
				# Verify if user is logged in
				if (AuthComponent::user('id')) {
					$this->request->data['User']['id'] = AuthComponent::user('id');
				} else # Maybe hes comming from change password form
				{
					# Check the hash in database
					$user = $this->User->findByHashChangePassword($this->request->data['User']['hash']);

					if (!empty($user)) {
						$this->request->data['User']['id'] = $user['User']['id'];

						# Clean users hash in database
						$this->request->data['User']['hash_change_password'] = '';
					} else {
						throw new MethodNotAllowedException(__('Invalid action'));
					}
				}

				if ($this->User->save($this->request->data)) {
					$this->Session->setFlash('Password updated successfully!', 'flash_success');
					$this->redirect(array('/home'));
				}
			} else {
				$this->Session->setFlash('Passwords do not match.', 'flash_fail');
			}
		}
	}

	/**
	 * Email form to inform the process of remembering the password.
	 * After entering the email is checked if this email is valid and if so, a message is sent containing a link to change your password
	 */
	public function remember_password() {
		if ($this->request->is('post')) {
			$user = $this->User->findByEmail($this->request->data['User']['email']);

			if (empty($user)) {
				$this->Session->setFlash('This email does not exist in our database.', 'flash_fail');
				$this->redirect(array('action' => 'login'));
			}

			$hash = $this->User->generateHashChangePassword();

			$data = array(
				'User' => array(
					'id' => $user['User']['id'],
					'hash_change_password' => $hash,
				),
			);

			$this->User->save($data);

			$email = new CakeEmail();
			$email->template('remember_password', 'default')
			      ->config('default')
			      ->emailFormat('html')
			      ->subject(__('Remember password - ' . Configure::read('Application.name')))
			      ->to($user['User']['email'])
			      // ->from(Configure::read('Application.from_email'))
			      ->from('itamar@cmgr.org')
			      ->viewVars(array('hash' => $hash))
			      ->send();

			$this->Session->setFlash('Check your e-mail to continue the process of recovering password.', 'flash_success');

		}
	}

	/**
	 * Step 2 to change the password.
	 * This step verifies that the hash is valid, if it is, show the form to the user to inform your new password
	 */
	public function remember_password_step_2($hash = null) {

		$user = $this->User->findByHashChangePassword($hash);

		if ($user['User']['hash_change_password'] != $hash || empty($user)) {
			throw new NotFoundException(__('Link invalid'));
		}

		# Sends the hash to the form to check before changing the password
		$this->set('hash', $hash);

		$this->render('/Users/change_password');

	}

	public function profile() {
	}

}

?>
