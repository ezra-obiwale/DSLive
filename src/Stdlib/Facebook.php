<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Facebook
 *
 * @author Ezra Obiwale <contact@ezraobiwale.com>
 */
class Facebook {

    private static $initialized;
    private static $website;

    /**
     * Initializes facebook plugins for the page.
     * 
     * Should be placed after the <b>body</b> tag
     * @return string
     */
    public static function init($appId, $website, $locale = 'en_GB') {
        if (self::$initialized)
            return null;
        ob_start();
        ?>
        <div id="fb-root"></div>
        <script>
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id))
                    return;
                js = d.createElement(s);
                js.id = id;
                js.src = "//connect.facebook.net/<?= $locale ?>/all.js#xfbml=1&appId=<?= $appId ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        </script>
        <?php
        self::$website = $website;
        self::$initialized = true;
        return ob_get_clean();
    }

    /**
     * Creates a like box
     * @param string $page The name of the page to like
     * @param array $options Keys include:<br />
     *      boolean <b>showFaces</b>[false]: Indicates whether to show the faces of those 
     * like the page<br />
     *      string <b>colorScheme</b>[light]: dark or light<br />
     *      boolean <b>header</b>[false]: Indicates whether to show the header<br />
     *      boolean <b>stream</b>[false]: Indicates whether to stream<br />
     *      boolean <b>border</b>[false]: Indicates whether to show border<br />
     *      string <b>class</b>: CSS class to attach to div<br />
     *      string <b>style</b>: CSS Styles to attach to div<br />
     * @return string
     */
    public static function likeBox($page, array $options = array()) {
        if ($ret = self::notInitialized())
            return $ret;
        $otions['showFaces'] = (@$options['showFaces']) ? 'true' : 'false';
        $otions['colorScheme'] = (@$options['colorScheme']) ? $options['colorScheme'] : 'light';
        $otions['header'] = (@$options['header']) ? 'true' : 'false';
        $otions['stream'] = (@$options['stream']) ? 'true' : 'false';
        $otions['border'] = (@$options['border']) ? 'true' : 'false';
        ob_start();
        ?>
        <div class="fb-like-box <?= @$options['class'] ?>" style="<?= @$otions['style'] ?>" data-href="http://www.facebook.com/<?= $page ?>" data-colorscheme="<?= $otions['colorScheme'] ?>" data-show-faces="<?= $otions['showFaces'] ?>" data-header="<?= $otions['header'] ?>" data-stream="<?= $otions['stream'] ?>" data-show-border="<?= $otions['border'] ?>"></div>
        <script>
            $(function() {
                $('.fb-like-box').attr('data-width', $('.fb-like-box').parent().width());
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Create a share button
     * @param string $type box_count | button_count | button | icon_link | icon | link
     * @param string $class CSS class to attach to container
     * @param string $style CSS style to attach to container
     * @return string
     */
    public static function share($type = 'button_count', $class = null, $style = null) {
        if ($ret = self::notInitialized())
            return $ret;
        ob_start();
        ?>
        <div class="fb-share-button <?= $class ?>" style="<?= $style ?>" data-href="<?= self::$website ?>" data-type="<?= $type ?>"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Create a like button
     * @param array $options Keys include:<br />
     *      boolean <b>showFaces</b>[false]: Indicates whether to show the faces of those 
     * like the page<br />
     *      int <b>width</b>[100]: Width of the like button<br />
     *      string <b>layout</b>[button_count]: standard | box_count | button_count | button<br />
     *      boolean <b>share</b>[false]: Indicates whether add a share button too<br />
     *      string <b>action</b>[like]: like | recommend<br />
     *      string <b>class</b>: CSS class to attach to div<br />
     *      string <b>style</b>: CSS style to attach to div<br />
     * @return string
     */
    public static function like(array $options = array()) {
        if ($ret = self::notInitialized())
            return $ret;
        $options['width'] = (@$options['width']) ? $options['width'] : '100';
        $options['layout'] = (@$options['layout']) ? $options['layout'] : 'button_count';
        $options['action'] = (@$options['action']) ? $options['action'] : 'like';
        $options['showFaces'] = (@$options['showFaces']) ? 'true' : 'false';
        $options['share'] = (@$options['share']) ? 'true' : 'false';
        ob_start();
        ?>
        <div class="fb-like <?= @$options['class'] ?>" style="<?= @$options['style'] ?>" data-href="<?= self::$website . $_SERVER['REQUEST_URI'] ?>" data-width="<?= $options['width'] ?>" data-layout="<?= $options['layout'] ?>" data-action="<?= $options['action'] ?>" data-show-faces="<?= $options['showFaces'] ?>" data-share="<?= $options['share'] ?>"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Create a like button
     * @param string $name Facebook username or group name
     * @param array $options Keys include:<br />
     *      boolean <b>showFaces</b>[false]: Indicates whether to show the faces of those 
     * like the page<br />
     *      int <b>width</b>[100]: Width of the like button<br />
     *      string <b>layout</b>[button_count]: standard | box_count | button_count | button<br />
     *      string <b>colorScheme</b>[light]: light | dark<br />
     *      string <b>action</b>[like]: like | recommend<br />
     *      string <b>class</b>: CSS class to attach to div<br />
     *      string <b>style</b>: CSS style to attach to div<br />
     * @return string
     */
    public static function follow($name, array $options = array()) {
        if ($ret = self::notInitialized())
            return $ret;
        $options['width'] = (@$options['width']) ? $options['width'] : '100';
        $options['height'] = (@$options['height']) ? $options['height'] : '100';
        $options['layout'] = (@$options['layout']) ? $options['layout'] : 'button_count';
        $options['action'] = (@$options['action']) ? $options['action'] : 'like';
        $options['showFaces'] = (@$options['showFaces']) ? 'true' : 'false';
        $options['share'] = (@$options['share']) ? 'true' : 'false';
        ob_start();
        ?>
        <div class="fb-like <?= @$options['class'] ?>" style="<?= @$options['style'] ?>" data-href="http://www.facebook.com/<?= $name ?>" data-width="<?= $options['width'] ?>" data-layout="<?= $options['layout'] ?>" data-action="<?= $options['action'] ?>" data-show-faces="<?= $options['showFaces'] ?>" data-share="<?= $options['share'] ?>"></div>
        <?php
        return ob_get_clean();
    }

    /**
     * Add comment box
     * @param string $class CSS class to add to the container
     * @param int $numPosts Maximum number of comments to show
     * @param string $colorScheme light | dark
     * @return string
     */
    public static function comments($class = null, $numPosts = 10, $colorScheme = 'light') {
        if ($ret = self::notInitialized())
            return $ret;
        ob_start();
        ?>
        <div class="fb-comments <?= $class ?>" data-href="<?= self::$website . $_SERVER['REQUEST_URI'] ?>" data-numposts="<?= $numPosts ?>" data-colorscheme="<?= $colorScheme ?>"></div>
        <script>
            $(function() {
                $('.fb-comments').attr('data-width', $('.fb-comments').parent().width()).css({
                    'overflow-y': 'visible'
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    private static function notInitialized() {
        // @todo check to ensure method works fine
        return false;
        if (!self::$initialized)
            return 'You need to call Facebook::init() before calling other methods';
        return false;
    }

}
