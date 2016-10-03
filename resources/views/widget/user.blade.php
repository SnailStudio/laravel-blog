<div class="widget widget-user" style="overflow: hidden">
    <?php
    if (isset($profile_image) && $profile_image)
        $style = "background: url($profile_image) center center;";
    else
        $style = "background-color: #607D8B;";
    ?>
    <div class="widget-user-header" style="{{ $style }}">
        <h3 class="widget-user-username">lufficc</h3>
        <h5 class="widget-user-desc">{{ $description or 'description' }}</h5>
    </div>
    <div class="widget-user-image">
        <img class="img-circle" src="{{ $avatar or '' }}" alt="User Avatar">
    </div>
    <div class="widget-user-footer">
        <div class="row">
            <div class="col-sm-3 border-right">
                <div class="description-block">
                    <a href="{{ config('social.weibo') }}" class="description-header"><i class="fa fa-weibo fa-lg"
                                                                                         aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="col-sm-3 border-right">
                <div class="description-block">
                    <a href="{{ config('social.github') }}" class="description-header fa-lg"><i
                                class="fa fa-github fa-lg" aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="col-sm-3 border-right">
                <div class="description-block">
                    <a href="{{ config('social.facebook') }}" class="description-header"><i
                                class="fa fa-facebook-official fa-lg"
                                aria-hidden="true"></i></a>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="description-block">
                    <a href="{{ config('social.twitter') }}" class="description-header"><i class="fa fa-twitter fa-lg"
                                                                                           aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>