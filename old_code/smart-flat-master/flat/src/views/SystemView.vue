<template>
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Навантаження системи 💻</h4>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 col-12">
                        <div class="card-box">
                            <div class="text-center">
                                <div class="show collapse" dir="ltr">
                                    <div
                                        id="cpu-charter"
                                        class="apex-charts"
                                        data-colors="#f1556c"
                                    ></div>
                                </div>
                                <!-- collapsed end -->
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3 col-6">
                        <div class="card-box">
                            <div class="text-center">
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup">{{
                                        memTotal
                                    }}</span
                                    >Mb
                                </h4>
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup">{{
                                        memLoad
                                    }}</span
                                    >Mb
                                </h4>
                                <p class="text-muted text-truncate mb-1">MEM</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 col-6">
                        <div class="card-box">
                            <div class="text-center">
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup"
                                        >S: {{ sda1 ? sda1.size : 0 }}</span
                                    >
                                </h4>
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup"
                                        >A: {{ sda1 ? sda1.avail : 0 }}</span
                                    >
                                </h4>
                                <p class="text-muted text-truncate mb-1">
                                    Бекапи
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3 col-6">
                        <div class="card-box">
                            <div class="text-center">
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup"
                                        >S: {{ sdb3 ? sdb3.size : 0 }}</span
                                    >
                                </h4>
                                <h4 class="text-dark my-1">
                                    <span data-plugin="counterup"
                                        >A: {{ sdb3 ? sdb3.avail : 0 }}</span
                                    >
                                </h4>
                                <p class="text-muted text-truncate mb-1">
                                    Медіа
                                </p>
                            </div>
                        </div>
                    </div>

                    <!--                    <div class="col-12">-->
                    <!--                        <div class="page-title-box">-->
                    <!--                            <h4 class="page-title">Керування сервером <span class="badge badge-danger">Увага!</span></h4>-->
                    <!--                        </div>-->
                    <!--                        <div class="d-flex flex-column align-items-start">-->
                    <!--                            <button data-toggle="modal" data-target="#confirm-reboot" type="button" class="btn btn-warning waves-effect waves-light mb-3">-->
                    <!--                                <span class="btn-label"><i class="mdi mdi-alert"></i></span>Перезавантажити-->
                    <!--                            </button>-->
                    <!--                            <button data-toggle="modal" data-target="#confirm-shutdown" type="button" class="btn btn-danger waves-effect waves-light">-->
                    <!--                                <span class="btn-label"><i class="mdi mdi-close-circle-outline"></i></span>Вимкнути-->
                    <!--                            </button>-->
                    <!--                        </div>-->
                    <!--                    </div>-->
                </div>
            </div>
        </div>

        <ConfirmAction
            id_modal="confirm-reboot"
            title="Підтвердити перезавантаження серверу?"
        >
            <button
                @click="reboot"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light mr-1"
            >
                Так
            </button>
            <button
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>
        <ConfirmAction
            id_modal="confirm-shutdown"
            title="Підтвердити вимкнення серверу?"
        >
            <button
                @click="shutDown"
                data-dismiss="modal"
                type="button"
                class="btn btn-danger waves-effect waves-light mr-1"
            >
                Так
            </button>
            <button
                data-dismiss="modal"
                type="button"
                class="btn btn-success waves-effect waves-light"
            >
                Ні
            </button>
        </ConfirmAction>
    </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useStore } from 'vuex';

import ConfirmAction from '@/components/ConfirmAction.vue';

export default {
    name: 'SystemView',
    components: {
        ConfirmAction,
    },
    setup() {
        const store = useStore();

        const cpuLoad = ref(0);
        const memTotal = ref(0);
        const memLoad = ref(0);

        const chartCpu = ref(null);

        const sda1 = ref({});
        const sdb3 = ref({});

        const lightColor = ref('#ffffff');

        const loopLiveLoad = ref(null);

        // Server actions
        async function reboot() {
            await store
                .dispatch('system/reboot')
                .then(
                    (response) => {},
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }
        async function shutDown() {
            await store
                .dispatch('system/shutDown')
                .then(
                    (response) => {},
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }

        // Disk space
        async function diskSpace() {
            await store
                .dispatch('system/getDiskSpace')
                .then(
                    (response) => {
                        const resData = response.data.data.res;
                        if (resData) {
                            sda1.value = resData.sda1;
                            sdb3.value = resData.sdb3;
                        }
                    },
                    () => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }
        onMounted(() => {
            diskSpace();
        });

        // CPU
        function initialCharterCpu() {
            var colors = ['#f1556c'];
            var dataColors = $('#cpu-charter').data('colors');
            if (dataColors) {
                colors = dataColors.split(',');
            }
            var options = {
                chart: {
                    height: 250,
                    type: 'radialBar',
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 135,
                        dataLabels: {
                            name: {
                                fontSize: '16px',
                                color: undefined,
                                offsetY: 50,
                            },
                            value: {
                                offsetY: -10,
                                fontSize: '22px',
                                color: undefined,
                                formatter: function (val) {
                                    return val + '%';
                                },
                            },
                        },
                    },
                },
                fill: {
                    gradient: {
                        enabled: true,
                        shade: 'dark',
                        shadeIntensity: 0.15,
                        inverseColors: false,
                        opacityFrom: 1,
                        opacityTo: 1,
                        stops: [0, 50, 65, 91],
                    },
                },
                stroke: {
                    dashArray: 4,
                },
                colors: colors,
                series: [0],
                labels: ['CPU'],
                responsive: [
                    {
                        breakpoint: 380,
                        options: {
                            chart: {
                                height: 250,
                            },
                        },
                    },
                ],
            };
            chartCpu.value = new ApexCharts(
                document.querySelector('#cpu-charter'),
                options,
            );
            chartCpu.value.render();
        }
        async function systemLoad() {
            await store
                .dispatch('system/getSystemLoad')
                .then(
                    (response) => {
                        const resData = response.data.data.res;
                        if (resData) {
                            chartCpu.value.updateSeries([
                                resData.cpu ? resData.cpu.use : 0.0,
                            ]);

                            memTotal.value = resData.mem
                                ? resData.mem.total
                                : 0;
                            memLoad.value = resData.mem ? resData.mem.used : 0;
                        }
                    },
                    (error) => {
                        alert('Auth error. Please call to your administrator.');
                    },
                )
                .finally(() => {
                    //
                });
        }
        onMounted(() => {
            setTimeout(function () {
                initialCharterCpu();
                systemLoad();
            }, 200);
        });
        onBeforeUnmount(() => {
            //chartCpu.value.destroy();
        });

        // Loop 2 life load
        onMounted(() => {
            clearTimeout(loopLiveLoad.value);
            loopLiveLoad.value = setInterval(function () {
                systemLoad();
            }, 2000);
        });
        onBeforeUnmount(() => {
            clearTimeout(loopLiveLoad.value);
        });

        return {
            cpuLoad,
            memTotal,
            memLoad,
            chartCpu,
            sda1,
            sdb3,
            reboot,
            shutDown,
        };
    },
};
</script>

<style scoped></style>
