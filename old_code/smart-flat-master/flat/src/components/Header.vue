<template>
    <div class="navbar-custom">
        <div class="container-fluid">
            <ul class="list-unstyled topnav-menu float-right mb-0">
                <li
                    v-if="false"
                    class="dropdown notification-list topbar-dropdown"
                >
                    <a
                        class="nav-link dropdown-toggle nav-user waves-effect waves-light mr-0"
                        data-toggle="dropdown"
                        href="#"
                        role="button"
                        aria-haspopup="false"
                        aria-expanded="false"
                    >
                        <img
                            src="/images/users/user-1.jpg"
                            alt="user-image"
                            class="rounded-circle"
                        />
                        <span class="pro-user-name ml-1">
                            Geneva <i class="mdi mdi-chevron-down"></i>
                        </span>
                    </a>
                    <div
                        class="dropdown-menu dropdown-menu-right profile-dropdown"
                    >
                        <!-- item-->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome !</h6>
                        </div>

                        <!-- item-->
                        <a
                            href="javascript:void(0);"
                            class="dropdown-item notify-item"
                        >
                            <i class="fe-user"></i>
                            <span>My Account</span>
                        </a>

                        <!-- item-->
                        <a
                            href="javascript:void(0);"
                            class="dropdown-item notify-item"
                        >
                            <i class="fe-settings"></i>
                            <span>Settings</span>
                        </a>

                        <!-- item-->
                        <a
                            href="javascript:void(0);"
                            class="dropdown-item notify-item"
                        >
                            <i class="fe-lock"></i>
                            <span>Lock Screen</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <!-- item-->
                        <a
                            href="javascript:void(0);"
                            class="dropdown-item notify-item"
                        >
                            <i class="fe-log-out"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </li>
            </ul>

            <!-- LOGO -->
            <div class="logo-box">
                <RouterLink to="/" class="logo text-center">
                    <span>
                        <img
                            src="/images/logo.png"
                            srcset="/images/logo@2x.png 2x"
                            width="35"
                        />
                    </span>
                </RouterLink>
            </div>

            <ul
                v-if="$store.getters['user/isAuth']"
                class="list-unstyled topnav-menu topnav-menu-left m-0"
            >
                <li>
                    <button class="button-menu-mobile waves-effect waves-light">
                        <i class="fe-menu"></i>
                    </button>
                </li>
                <li>
                    <!-- Mobile menu toggle (Horizontal Layout)-->
                    <a
                        class="navbar-toggle nav-link"
                        data-toggle="collapse"
                        data-target="#topnav-menu-content"
                    >
                        <div class="lines">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </a>
                    <!-- End mobile menu toggle-->
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>
    </div>
</template>
