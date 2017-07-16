<?php
	class ImageRoutes {

		public function showUnknown($displayEngine) {
			$displayEngine->setPageID('images')->setTitle('Bootable Images :: Unknown');
			$displayEngine->display('images/unknown.tpl');
		}

		public function addRoutes($router, $displayEngine, $api) {
			$router->get('/images(.json)?', function($json = true) use ($displayEngine, $api) {
				$displayEngine->setPageID('images')->setTitle('Bootable Images');

				$images = $api->getBootableImages();
				if ($json) {
					header('Content-Type: application/json');
					echo json_encode(['images' => $images]);
					return;
				}

				$displayEngine->setVar('images', $images);

				$displayEngine->display('images/index.tpl');
			});

			$router->get('/images/create', function() use ($displayEngine, $api) {
				$displayEngine->setPageID('images')->setTitle('Bootable Images :: Create');

				$displayEngine->display('images/create.tpl');
			});

			$router->get('/images/([0-9]+)', function($imageid) use ($router, $displayEngine, $api) {
				$image = $api->getBootableImage($imageid);
				if (!($image instanceof BootableImage)) { return $this->showUnknown($displayEngine); }

				$displayEngine->setVar('image', $image->toArray());

				$displayEngine->setPageID('images')->setTitle('Bootable Images :: ' . $image->getName());

				$displayEngine->display('images/view.tpl');
			});

			$router->post('/images/create.json', function() use ($router, $displayEngine, $api) {
				$this->doCreateOrEdit($api, $displayEngine, NULL, $_POST);
			});


			$router->post('/images/([0-9]+)/edit.json', function($imageid) use ($router, $displayEngine, $api) {
				$this->doCreateOrEdit($api, $displayEngine, $imageid, $_POST);
			});

			$router->post('/images/([0-9]+)/delete', function($imageid) use ($router, $displayEngine, $api) {
				$image = $api->getBootableImage($imageid);
				if (!($image instanceof BootableImage)) { return $this->showUnknown($displayEngine); }

				if (isset($_POST['confirm']) && parseBool($_POST['confirm'])) {
					$result = $image->delete();
					if ($result) {
						$displayEngine->flash('success', '', 'Image ' . $image->getName() . ' has been deleted.');
						header('Location: ' . $displayEngine->getURL('/images'));
						return;
					} else {
						$displayEngine->flash('error', '', 'There was an error deleting the image.');
						header('Location: ' . $displayEngine->getURL('/images/' . $imageid));
						return;
					}
				} else {
					header('Location: ' . $displayEngine->getURL('/images/' . $imageid));
					return;
				}
			});
		}

		function doCreateOrEdit($api, $displayEngine, $imageid, $data) {
			if ($imageid !== NULL) {
				[$result,$resultdata] = $api->editBootableImage($imageid, $_POST);
			} else {
				[$result,$resultdata] = $api->createBootableImage($_POST);
			}

			if ($result) {
				$displayEngine->flash('success', '', 'Your changes have been saved.');

				header('Content-Type: application/json');
				echo json_encode(['success' => 'Your changes have been saved.', 'location' => $displayEngine->getURL('/images/' . $resultdata)]);
				return;
			} else {
				header('Content-Type: application/json');
				echo json_encode(['error' => 'There was an error with the data provided: ' . $resultdata]);
				return;
			}
		}
	}