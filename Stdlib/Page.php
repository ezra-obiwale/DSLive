<?php

use DBScribe\ArrayCollection,
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
     * @return string
     */
    public static function mediaCarousel(ArrayCollection $media) {
        $items = array();
        $fo = new FileOut();
        if ($media && !is_bool($media)) {
            foreach ($media as $key => $medium) {
                $items[] = array(
                    'img' => $fo(method_exists($medium, 'getPath') ? $medium->getPath() : $medium->path, array(
                        'attrs' => array(
                            'style' => 'max-height:340px',
                        )
                    )),
                    'caption' => '<h4>' . (method_exists($medium, 'getName') ? $medium->getName() : $medium->name) . '</h4><p>' . (method_exists($medium, 'getDescription') ? $medium->getDescription() : $medium->description) . '</p>',
                    'active' => ($key === 0),
                );
            }
        }
        return TwBootstrap::carousel($items, array('class' => ''));
    }

    /**
     * Fetches media from the database and creates a carousel from the results
     * 
     * @param string $table
     * @param array $criteria
     * @return string
     */
    public static function mediaCarouselFromDB($table = 'media', array $criteria = array()) {
        return self::mediaCarousel(Engine::getDB()->table($table)->select($criteria));
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
                ->select(array(array('status' => 1)));

        ob_start();
        ?>
        <ul class="nav">
            <?php
            foreach ($categories as $category) {
                if ($category->name === '-- None --')
                    continue;
                $pages = $category->page(array(
                    'orderBy' => 'position',
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
                    <a href="#" <?= ($pages->count()) ? 'class="dropdown-toggle" data-toggle="dropdown"' : '' ?>><?= $category->name ?> <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?= $lis ?>
                    </ul>
                </li>
                <?php
                $subCategories = $category->category(array(
                    'orderBy' => 'position'
                ));
                if ($subCategories->count()) {
                    foreach ($subCategories as $sub) {
                        $pages = $sub->page(array(
                            'orderBy' => 'position'
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
                        <li <?= $active ? 'class="active"' : '' ?>>
                            <a href="#" <?= ($pages->count()) ? 'class="dropdown-toggle" data-toggle="dropdown"' : '' ?>><?= $category->name ?> <b class="caret"></b></a>
                            <ul class="dropdown-submenu">
                                <?= $lis ?>
                            </ul>
                        </li>
                        <?php
                    }
                }
            }
            ?>
            <li><a href="<?= $renderer->url('cms', 'media', 'gallery') ?>">PHOTO GALLERY</a></li>
        </ul>
        <?php
        return ob_get_clean();
    }

}
