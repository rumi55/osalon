<div class="app-sidebar sidebar-shadow" style="background: #fafafa">
    <div class="app-header__logo">
        <div class="logo-src"></div>
        <div class="header__pane ml-auto">
            <div>
                <button type="button" class="hamburger close-sidebar-btn hamburger--elastic" data-class="closed-sidebar">
                    <span class="hamburger-box">
                    <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <div class="app-header__mobile-menu">
        <div>
            <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                 <span class="hamburger-box">
                 <span class="hamburger-inner"></span>
                 </span>
            </button>
        </div>
    </div>
    <div class="app-header__menu">
      <span>
          <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
              <span class="btn-icon-wrapper">
              <i class="fa fa-ellipsis-v fa-w-6"></i>
              </span>
          </button>
      </span>
    </div>
    <div class="scrollbar-sidebar">
        <div class="app-sidebar__inner">
            <ul class="vertical-nav-menu">
                <li class="app-sidebar__heading"> </li>
                <li>
                    <a href="{{route("dashboard")}}" class="">
                        <i class="metismenu-icon fa fa-home"></i>
                        Accueil
                    </a>
                </li>
                @can('Pressings')
                    <li>
                        <a href="{{route("salon.index")}}" class="{{$active == "salon" ? "mm-active" : ""}}">
                            <i class="metismenu-icon fa fa-tachometer-alt"></i>
                            Salons
                        </a>
                    </li>
                @endcan

                @can('Abonnements')
                    <li>
                        <a href="{{route("abonnement.index")}}" class="{{$active == "abonnement" ? "mm-active" : ""}}">
                            <i class="metismenu-icon fa fa-credit-card"></i>
                            Abonnement
                        </a>
                    </li>
                @endcan

                @can('Transactions')
                    <li>
                        <a href="{{route("transaction.index")}}" class="{{$active == "transaction" ? "mm-active" : ""}}">
                            <i class="metismenu-icon fa fa-coins"></i>
                            Transactions
                        </a>
                    </li>
                @endcan

                @can('Offres')
                    <li>
                        <a href="{{route("offre.index")}}" class="{{$active == "offre" ? "mm-active" : ""}}">
                            <i class="metismenu-icon fa fa-gift"></i>
                            Offre
                        </a>
                    </li>
                @endcan

                @can('SMS')
                    <li>
                        <a href="{{route("sms.create")}}" class="{{$active == 'sms' ? 'mm-active' : ''}}">
                            <i class="metismenu-icon fa fa-paper-plane"></i>
                            SMS
                        </a>
                    </li>
                @endcan

            </ul>
        </div>
    </div>
</div>
