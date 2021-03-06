<?php

use Cms\Models\Slide,
    DBScribe\ArrayCollection,
    DScribe\View\Renderer;

/*
 */

/**
 * Description of Page
 *
 * @author topman
 */
class Page {

    /**
     * Creates a carousel collection of media
     * 
     * @param ArrayCollection $media
     * @param array $attrs Attributes of the carousel
     * @return string
     */
    public static function mediaCarousel(ArrayCollection $media, array $attrs = array()) {
        $items = array();
        $fo = new FileOut();
        if ($media && !is_bool($media)) {
            foreach ($media as $key => $medium) {
                $caption = '';
                $name = (method_exists($medium, 'getName') ? $medium->getName() : $medium->name);
                $id = preg_replace('/[^a-zA-Z0-9]/', '-', isset($attrs['class']) ? $attrs['class'] . ' ' . $name : $name);
                if (!isset($attrs['name']) || (isset($attrs['name']) && $attrs['name'])) {
                    $caption .= '<h4>' . $name . '</h4>';
                }
                $description = (method_exists($medium, 'getDescription') ? $medium->getDescription() : $medium->description);
                if ((!isset($attrs['description']) || (isset($attrs['description']) && $attrs['description'])) && $description) {
                    $caption .= '<p>' . $description . '</p>';
                }
                $items[$key] = array(
                    'img' => $fo(($medium && method_exists($medium, 'getPath')) ? $medium->getPath() : $medium->path, array(
                        'attrs' => array(
                            'id' => $id,
                        )
                    )),
                    'active' => ($key === 0),
                );

                if (!empty($caption) && (!isset($attrs['caption']) || (isset($attrs['caption']) && $attrs['caption']))) {
                    $items[$key]['caption'] = $caption;
                }
            }
        }

        unset($attrs['name']);
        return TwBootstrap::carousel($items, $attrs);
    }

    /**
     * Fetches media from the database and creates a carousel from the results
     * 
     * @param string $table
     * @param array $criteria
     * @return string
     */
    public static function mediaCarouselFromDB($table = 'media', array $criteria = array(), array $attrs = array()) {
        return self::mediaCarousel(engineGet('db')->table($table)->select($criteria), $attrs);
    }

    public static function carouselFromModelsWithMedia(ArrayCollection $objects, array $attrs = array()) {
        $media = new ArrayCollection();
        foreach ($objects as $object) {
            $media->add($object->media()->first());
        }
        return self::mediaCarousel($media, $attrs);
    }

    public static function social(array $links) {
        $return = '<ul class="social">';
        foreach ($links as $label => $link) {
            if (strtolower(substr($link, 0, 7)) !== 'http://' && strtolower(substr($link, 0, 8)) !== 'https://')
                $link = 'http://' . $link;
            $return .= '<li><a target="_blank" href="' . $link . '">' . $label . '</a></li>';
        }
        return $return . '</ul>';
    }

    public static function cmsLinks(Renderer $renderer, $id = null, array $additionalLinks = array()) {
        $categories = engineGet('db')
                ->table('category')
                ->orderBy('position')
                ->orderBy('name')
                ->customWhere('category.parent IS NULL')
//                ->join('category')
//                ->join('page')
                ->select(array(array(
                'status' => 1,
        )));
        ob_start();
        ?>
        <ul class="nav cms-nav">
            <?php
            foreach ($categories as $category) {
                if ($category->name === '-- None --')
                    continue;
                $pages = $category->page(array(
                    'orderBy' => 'position',
                    'where' => array(array(
                            'status' => 1
                        ))
                ));
                $subCategories = $category->category(array(
                    'orderBy' => 'position',
                    'push' => true,
                    'where' => array(array(
                            'status' => 1
                        ))
                ));

                $active = false;
                ob_start();
                foreach ($pages as $page) {
                    if ($id === $page->id)
                        $active = true;
                    ?>
                    <li  <?= ($id === $page->id) ? 'class="active"' : '' ?>><a data-id="<?= $page->id ?>" class="auto" tabindex="-1" href="<?= $renderer->url('cms', 'page', 'view', array($category->link, $page->link)) ?>"><?= $page->title ?></a></li>
                    <?php
                }
                $lis = ob_get_clean();
                ?>
                <li class="dropdown <?= $active ? 'active' : '' ?>">
                    <a tabindex="-1" data-id="<?= $category->id ?>" href="#" <?= ($pages->count() || $subCategories->count()) ? 'class="dropdown-toggle" data-toggle="dropdown"' : '' ?>><?= $category->name ?> <b class="caret"></b></a>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
                        <?= $lis ?>
                        <?php
                        if ($subCategories->count()) {
                            foreach ($subCategories as $sub) {
                                $pages = $sub->page(array(
                                    'orderBy' => 'position',
                                    'where' => array(array(
                                            'status' => 1
                                        ))
                                ));
                                $active = false;
                                ob_start();
                                foreach ($pages as $page) {
                                    if ($id === $page->id)
                                        $active = true;
                                    ?>
                                    <li <?= ($id === $page->id) ? 'class="active"' : '' ?>><a tabindex="-1" class="auto" href="<?= $renderer->url('cms', 'page', 'view', array($sub->link, $page->link)) ?>"><?= $page->title ?></a></li>
                                    <?php
                                }
                                $lis = ob_get_clean();
                                ?>
                                <li class="dropdown-submenu <?= $active ? 'active' : '' ?>">
                                    <a href="#" tabindex="-1"class="dropdown-toggle" data-toggle="dropdown"><?= $sub->name ?></b></a>
                                    <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
                                        <?= $lis ?>
                                    </ul>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </li>
                <?php
            }
            foreach ($additionalLinks as $link => $label) {
                if (!is_array($label)) {
                    ?>
                    <li><a tabindex="-1" href="<?= $link ?>"><?= $label ?></a></li>
                    <?php
                }
                else {
                    if (empty($label['liAttrs'])) {
                        $label['liAttrs'] = array();
                    }
                    if (empty($label['linkAttrs'])) {
                        $label['linkAttrs'] = array();
                    }
                    ?>
                    <li <?= TwBootstrap::parseAttributes($label['liAttrs']) ?>>
                        <a tabindex="-1" <?= TwBootstrap::parseAttributes($label['linkAttrs']) ?> href="<?= $link ?>"><?= $label['label'] ?></a>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
        <?php
        return ob_get_clean();
    }

    public static function cleanContent($content) {
        $sep = '_:DS:_';
        if ($cont = self::insertSlides($content, $sep))
            $content = $cont;
        $content = self::insertForms($content, $sep);
        return str_replace('../../../../media', '/media', $content);
    }

    public static function insertSlides($content, $sep = '_:DS:_', $attrs = array()) {
        if (!$sep)
            $sep = '_:DS:_';

        $slides = self::getSlides($sep, $content);
        foreach ($slides as $slide) {
            if ($slide->getWidth()) {
                $attrs['style'] = 'width:' . $slide->getWidth();
            }
            if ($slide->getHeight()) {
                if (isset($attrs['style']))
                    $attrs['style'] .= ';height:' . $slide->getHeight();
                else
                    $attrs['style'] = 'height:' . $slide->getHeight();
            }
            $attrs['name'] = false;
            $attrs['id'] = str_replace(' ', '-', $slide->getCodeName());
            $content = str_replace('{slide' . $sep . $slide->getCodeName() . '}', self::mediaCarousel($slide->media(array(
                                'orderBy' => 'name'
                            )), $attrs), $content);
        }
        return ($slides && $slides->count()) ? $content : null;
    }

    public static function insertForms($content, $sep = '_:DS:_') {
        foreach (self::getForms($sep, $content) as $formAttrs) {
            if (empty($formAttrs))
                continue;

            if ($formModel = \Session::fetch('form')) {
                \Session::remove('form');
            }
            else
                $formModel = new $formAttrs[1];

            $currentPath = serialize(engineGet('urls'));
            ob_start();
            echo TwbForm::horizontal($formModel->setAttribute('action', $formModel->getAttribute('action') . '/' . urlencode($currentPath)));
            $content = str_replace('{form' . $sep . join($sep, $formAttrs) . '}', ob_get_clean(), $content);
        }
        return $content;
    }

    private static function getSlides($sep, $content) {
        $slides = array();
        $pos = stripos($content, 'slide' . $sep);
        if (!empty($pos)) {
            $slideStr = substr($content, $pos, stripos($content, '}', $pos) - $pos);
            $codeName = str_replace('slide' . $sep, '', $slideStr);
            $slides[] = array('codeName' => $codeName);
        }
        return (!empty($slides)) ? engineGet('db')->table('slide', new Slide())->select($slides) : array();
    }

    private static function getForms($sep, $content, $offset = 0) {
        $forms = array();
        $pos = stripos($content, '{form' . $sep, $offset);
        if (!empty($pos)) {
            $endPos = stripos($content, '}', $pos) - $pos - 1;
            $formStr = substr($content, $pos + 1, $endPos);
            $attrs = explode($sep, $formStr);
            unset($attrs[0]);
            $forms[] = $attrs;
//            $forms = array_merge($forms, $content, $this->getForms($sep, $endPos));
        }

        return $forms;
    }

}
