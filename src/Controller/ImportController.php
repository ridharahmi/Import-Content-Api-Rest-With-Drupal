<?php


namespace Drupal\import_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Exception\RequestException;
use Drupal\node\Entity\Node;

class ImportController extends ControllerBase
{


    /**
     * {@inheritdoc}
     */
    public function content()
    {
        $client = \Drupal::httpClient();
        $url = 'http://deploy:deploy_bitbucket_2017@investia-intra.elyosdigital.com/api/pme/passed';
        try {
            $response = $client->get($url);
            $data = $response->getBody();
            $response = (object)json_decode($data, true);
            //kint($response);
            foreach ($response->data as $items) {
                $nid = $this->isItemExiste($items['id']);
                $id = reset($nid);
                if ($id) {
                    $node = Node::load($id);
                    $node->title = $items['raison_soc'];
                    $node->field_image_enterprise = $items['logo'];
                    $node->save();
                }else{
                    $node = Node::create([
                        'type' => 'enterprise',
                        'field_index' => $items['id'],
                        'title' => $items['raison_soc'],
                        'field_image_enterprise' => [
                            'value' => $items['logo'],
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


}

