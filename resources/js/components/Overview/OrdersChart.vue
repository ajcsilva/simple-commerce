<template>
    <div class="card p-2 content mb-2">
        <div class="flex justify-between items-center p-2">
            <h2 class="font-semibold text-base">
                {{ __('Number of Orders (Last 30 Days)') }}
            </h2>
        </div>

        <div class="flex flex-wrap -mx-2 mb-4">
            <div class="px-2 w-full">
                <div class="px-1" v-if="ready">
                    <LineChartGenerator
                        :chart-options="chartOptions"
                        :chart-data="chartData"
                        :chart-id="'overview-orders-chart'"
                        :dataset-id-key="'label'"
                        :cssClasses="''"
                        :styles="{}"
                        :plugins="[]"
                        :width="'400'"
                        :height="'200'"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { Line as LineChartGenerator } from 'vue-chartjs/legacy'

import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    LinearScale,
    CategoryScale,
    PointElement,
} from 'chart.js'

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    LinearScale,
    CategoryScale,
    PointElement
)

export default {
    components: {
        LineChartGenerator,
    },

    props: {
        data: Array,
    },

    data() {
        return {
            ready: false,

            chartData: {
                labels: [],
                datasets: [
                    {
                        label: 'Paid Orders',
                        backgroundColor: '#16a34a',
                        data: [],
                    },
                ],
            },

            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
            },
        }
    },

    mounted() {
        this.data.forEach((item) => {
            this.chartData.labels.push(item.date)
            this.chartData.datasets[0].data.push(item.count)
        })

        this.ready = true
    },
}
</script>
