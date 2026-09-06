<template>
    <div v-if="$store.getters['user/isAuth']" class="left-side-menu">
        <div class="h-100" data-simplebar>
            <!--- Side menu -->
            <div id="sidebar-menu">
                <ul id="side-menu">
                    <li>
                        <RouterLink to="/">
                            <i class="fas fa-home"></i>
                            <span>Головна</span>
                        </RouterLink>
                        <RouterLink to="/media">
                            <i class="fas fa-photo-video"></i>
                            <span>Медіа</span>
                        </RouterLink>
                        <RouterLink to="/smart-frame">
                            <i class="fas fa-paint-brush"></i>
                            <span>Розумна рамка</span>
                        </RouterLink>
                        <RouterLink to="/system">
                            <i class="fas fa-wrench"></i>
                            <span>Система</span>
                        </RouterLink>
                    </li>
                </ul>
            </div>
            <!-- End Sidebar -->
            <div class="clearfix"></div>
        </div>
        <!-- Sidebar -left -->
    </div>
</template>

<script>
export default {
    name: 'Menu',
};
</script>

<style scoped></style>
