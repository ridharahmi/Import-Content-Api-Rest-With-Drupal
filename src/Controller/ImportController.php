<?php


namespace Drupal\import_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\node\Entity\Node;
use \Drupal\file\Entity\File;

class ImportController extends ControllerBase
{


    /**
     * {@inheritdoc}
     */
    public function content()
    {
        $client = \Drupal::httpClient();
        $url = 'http://exemple.com/api';
        try {
            $response = $client->get($url);
            $data = $response->getBody();
            $response = (object)json_decode($data, true);
            //kint($response);
            foreach ($response->data as $items) {
                $nid = $this->isItemExiste($items['id']);
                $id = reset($nid);
                if (!$items['logo']) {
                    $items['logo'] = file_create_url(drupal_get_path('module', 'import_content') . '/assets/img/logo.png');
                }
                $uri = $items['logo'];
                $file = $this->prepareImageObj($uri);

                if ($id) {
                    $node = Node::load($id);
                    $node->title = $items['raison_soc'];
                    $node->field_image_enterprise = [
                        'value' => $items['logo'],
                    ];
                    $node->field_logo[] = [
                        'target_id' => $file->id(),
                        'alt' => $items['raison_soc'],
                        'title' => $items['raison_soc'],
                    ];

                    $node->save();
                } else {
                    $node = Node::create([
                        'type' => 'enterprise',
                        'field_index' => $items['id'],
                        'title' => $items['raison_soc'],
                        'field_image_enterprise' => [
                            'value' => $items['logo'],
                        ],
                        'field_logo' => [
                            'target_id' => $file->id(),
                            'alt' => $items['raison_soc'],
                            'title' => $items['raison_soc'],
                        ],
                        'moderation_state' => [
                            'target_id' => 'published',
                        ],
                        'uid' => 1,
                        'langcode' => 'en',
                        'status' => 1,
                    ]);
                    $node->save();
                }
            }
        } catch (RequestException $e) {
            watchdog_exception('import_content', $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    private function isItemExiste($id)
    {
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'enterprise')
            ->condition('field_index', $id)
            ->execute();
        return $nids;
    }

    /**
     * {@inheritdoc}
     */
    private function prepareImageObj($url)
    {
        $files = \Drupal::entityTypeManager()
            ->getStorage('file')
            ->loadByProperties(['uri' => $url]);
        $file = reset($files);

        // if not create a file
        if (!$file) {
            $file = File::create([
                'uri' => $url,
            ]);
            $file->save();
        }
        return $file;
    }

}

