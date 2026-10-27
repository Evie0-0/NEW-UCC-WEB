
function createSparkline(id, data, color) {
    const element = document.querySelector(id);

    if (!element) {
        console.warn("Chart element not found:", id);
        return;
    }

    const chart = new ApexCharts(element, {
        chart: {
            type: "area",
            height: 56,
            sparkline: {
                enabled: true
            },
            toolbar: {
                show: false
            },
            animations: {
                enabled: true,
                easing: "easeinout",
                speed: 900
            }
        },
        series: [{
            name: "Activity",
            data: data
        }],
        stroke: {
            curve: "smooth",
            width: 3
        },
        colors: [color],
        fill: {
            type: "gradient",
            gradient: {
                shadeIntensity: 0.35,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 100]
            }
        },
        markers: {
            size: 0
        },
        grid: {
            show: false
        },
        tooltip: {
            enabled: false
        }
    });

    chart.render();
}

function createTopPostsChart() {
    const element = document.querySelector("#topPostsChart");

    if (!element) {
        console.warn("Top posts chart element not found.");
        return;
    }

    const chart = new ApexCharts(element, {
        chart: {
            type: "bar",
            height: 350,
            toolbar: {
                show: false
            },
            fontFamily: "DM Sans, sans-serif",
            background: "transparent"
        },
        series: [{
            name: "Views",
            data: [5482, 3921, 2876, 2153, 1904]
        }],
        colors: [
            "#0b744b",
            "#1a8b5d",
            "#37a56f",
            "#58bf85",
            "#d8b056"
        ],
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: "50%",
                borderRadius: 8,
                distributed: true
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -10,
            style: {
                fontSize: "12px",
                fontWeight: 700,
                colors: ["#27483f"]
            },
            formatter: function (value) {
                return value.toLocaleString();
            }
        },
        xaxis: {
            categories: [
                "UCC Heroes",
                "SSC",
                "VISION",
                "Dekalidad",
                "Community"
            ],
            labels: {
                style: {
                    fontSize: "12px",
                    colors: "#42544c"
                }
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            }
        },
        yaxis: {
            min: 0,
            max: 6000,
            tickAmount: 6,
            title: {
                text: "Views",
                style: {
                    fontSize: "13px",
                    fontWeight: 700,
                    color: "#365046"
                }
            },
            labels: {
                formatter: function (value) {
                    return Math.round(value).toLocaleString();
                }
            }
        },
        grid: {
            borderColor: "#ebe5d8",
            strokeDashArray: 5
        },
        legend: {
            show: false
        },
        tooltip: {
            y: {
                formatter: function (value) {
                    return value.toLocaleString() + " views";
                }
            }
        }
    });

    chart.render();
}


function initializeDashboard() {
    if (typeof window.ApexCharts === "undefined") {
        console.error("ApexCharts did not load. Check your internet connection.");
        return;
    }

    createSparkline(
        "#chartPosts",
        [18, 25, 21, 32, 29, 40, 52],
        "#38d96c"
    );

    createSparkline(
        "#chartPending",
        [22, 27, 25, 31, 28, 36, 40],
        "#d0a214"
    );

    createSparkline(
        "#chartArchived",
        [46, 42, 38, 36, 33, 31, 28],
        "#9aa5aa"
    );

    createSparkline(
        "#chartUsers",
        [14, 18, 17, 24, 28, 31, 36],
        "#e04444"
    );

    createSparkline(
        "#chartVisitors",
        [12, 26, 18, 34, 28, 40, 48],
        "#5146c8"
    );

    createTopPostsChart();

    if (window.lucide) {
        window.lucide.createIcons();
    }

    console.log("Dashboard charts initialized.");
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeDashboard);
} else {
    initializeDashboard();
}

