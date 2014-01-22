<?php

use Cms\Models\Slide,
    DBScribe\ArrayCollection,
    DScribe\Core\Engine,
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
                $items[] = array(
                    'img' => $fo(method_exists($medium, 'getPath') ? $medium->getPath() : $medium->path),
                    'caption' => '<h4>' . (method_exists($medium, 'getName') ? $medium->getName() : $medium->name) . '</h4><p>' . (method_exists($medium, 'getDescription') ? $medium->getDescription() : $medium->description) . '</p>',
                    'active' => ($key === 0),
                );
            }
        }

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
        return self::mediaCarousel(Engine::getDB()->table($table)->select($criteria), $attrs);
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

    public static function cmsLinks(Renderer $renderer, $model = null) {
        $categories = Engine::getDB()
                ->table('category')
                ->orderBy('position')
                ->orderBy('name')
                ->customWhere('parent IS NULL')
                ->select(array(array(
                'status' => 1,
        )));
        ob_start();
        ?>
        <ul class="nav">
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
                    'where' => array(array(
                            'status' => 1
                        ))
                ));

                $active = false;
                ob_start();
                foreach ($pages as $page) {
                    if ($model && $model->getId() === $page->id)
                        $active = true;
                    ?>
                    <li <?= ($model && $model->getId() === $page->id) ? 'class="active"' : '' ?>><a href="<?= $renderer->url('cms', 'page', 'view', array($category->link, $page->link)) ?>"><?= $page->title ?></a></li>
                    <?php
                }
                $lis = ob_get_clean();
                ?>
                <li class="dropdown <?= $active ? 'active' : '' ?>">
                    <a href="#" <?= ($pages->count() || $subCategories->count()) ? 'class="dropdown-toggle" data-toggle="dropdown"' : '' ?>><?= $category->name ?> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
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
                                    if ($model && $model->getId() === $page->id)
                                        $active = true;
                                    ?>
                                    <li <?= ($model && $model->getId() === $page->id) ? 'class="active"' : '' ?>><a href="<?= $renderer->url('cms', 'page', 'view', array($sub->link, $page->link)) ?>"><?= $page->title ?></a></li>
                                    <?php
                                }
                                $lis = ob_get_clean();
                                ?>
                                <li class="dropdown-submenu <?= $active ? 'active' : '' ?>">
                                    <a href="#" tabindex="-1"><?= $sub->name ?></b></a>
                                    <ul class="dropdown-menu">
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
            ?>
            <!--<li><a href="<?= $renderer->url('cms', 'media', 'gallery') ?>">PHOTO GALLERY</a></li>-->
        </ul>
        <?php
        return ob_get_clean();
    }

    public static function cleanContent($content) {
        $sep = '_:DS:_';
        self::insertSlides($content, $sep);
        self::insertForms($content, $sep);
        return $content;
    }

    private static function insertSlides(&$content, $sep) {
        foreach (self::getSlides($sep, $content) as $slide) {
            $attrs = array('id' => $slide->getCodeName());
            if ($slide->getWidth()) {
                $attrs['style'] = 'width:' . $slide->getWidth();
            }
            if ($slide->getHeight()) {
                if (isset($attrs['style']))
                    $attrs['style'] .= ';height:' . $slide->getHeight();
                else
                    $attrs['style'] = 'height:' . $slide->getHeight();
            }
            $content = str_replace('{slide' . $sep . $slide->getCodeName() . '}', self::mediaCarousel($slide->media(), $attrs), $content);
        }
    }

    private static function insertForms(&$content, $sep) {
        foreach (self::getForms($sep, $content) as $formAttrs) {
            if (empty($formAttrs))
                continue;
            $formModel = new $formAttrs[1];
            if ($post = Session::fetch('post')) {
                $formModel->setData($post);
//                Session::remove('post');
                if ($errors = Session::fetch('postErrors')) {
                    $formModel->setErrors($errors);
//                    Session::remove('postErrors');
                }
            }
            $currentPath = serialize(Engine::getUrls());
            ob_start();
            echo TwbForm::horizontal($formModel->setAttribute('action', $formModel->getAttribute('action') . '/' . urlencode($currentPath)));
            $content = str_replace('{form' . $sep . join($sep, $formAttrs) . '}', ob_get_clean(), $content);
        }
    }

    private static function getSlides($sep, $content) {
        $slides = array();
        $pos = stripos($content, 'slide' . $sep);
        if (!empty($pos)) {
            $slideStr = substr($content, $pos, stripos($content, '}', $pos) - $pos);
            $codeName = str_replace('slide' . $sep, '', $slideStr);
            $slides[] = array('codeName' => $codeName);
        }
        return Engine::getDB()->table('slide', new Slide())->select($slides);
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
