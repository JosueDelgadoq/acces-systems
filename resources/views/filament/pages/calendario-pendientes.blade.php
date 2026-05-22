<x-filament-panels::page>
    @php
        $summary = $this->getSummary();
        $technicians = $this->getTechnicians();
    @endphp

    <div
        x-data="pendientesCalendar({
            feedUrl: @js($this->getCalendarFeedUrl()),
            updateUrl: @js($this->getCalendarUpdateUrl()),
            csrfToken: @js(csrf_token()),
        })"
        x-init="init()"
        class="calendar-page"
    >
        <section class="calendar-hero">
            <div>
                <span class="calendar-eyebrow">Planificacion tecnica</span>
                <h1 class="calendar-title">Calendario de pendientes</h1>
                <p class="calendar-subtitle">
                    Vista mensual para asignacion, seguimiento y reprogramacion de visitas tecnicas sin perder contexto operativo.
                </p>
            </div>

            <div class="calendar-toolbar">
                <label class="calendar-filter">
                    <span>Tecnico</span>
                    <select x-model="userId" @change="reloadEvents()">
                        <option value="">Todos los tecnicos</option>
                        @foreach ($technicians as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="calendar-legend">
                    <span><i class="dot dot-high"></i> Alta</span>
                    <span><i class="dot dot-medium"></i> Media</span>
                    <span><i class="dot dot-low"></i> Baja</span>
                </div>
            </div>
        </section>

        <section class="calendar-kpis">
            <article class="calendar-kpi">
                <span>Programados</span>
                <strong>{{ number_format($summary['scheduled'], 0, ',', '.') }}</strong>
            </article>
            <article class="calendar-kpi">
                <span>Hoy</span>
                <strong>{{ number_format($summary['today'], 0, ',', '.') }}</strong>
            </article>
            <article class="calendar-kpi">
                <span>Vencidos</span>
                <strong>{{ number_format($summary['overdue'], 0, ',', '.') }}</strong>
            </article>
            <article class="calendar-kpi">
                <span>Sin asignar</span>
                <strong>{{ number_format($summary['unassigned'], 0, ',', '.') }}</strong>
            </article>
        </section>

        <section class="calendar-shell">
            <div class="calendar-shell-header">
                <div>
                    <h2>Agenda mensual</h2>
                    <p>Arrastra una tarea a otra fecha para reprogramarla. Click sobre un evento para abrir su edicion.</p>
                </div>
                <div class="calendar-status" x-text="statusText"></div>
            </div>

            <div x-show="errorMessage" x-text="errorMessage" class="calendar-alert" x-cloak></div>

            <div x-ref="calendar" class="calendar-canvas"></div>
        </section>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">

    <style>
        .calendar-page { display: grid; gap: 1.5rem; }
        .calendar-hero {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: end;
            padding: 1.5rem;
            border: 1px solid var(--erp-border);
            border-radius: 24px;
            background: linear-gradient(135deg, #0f1e33 0%, #16375a 58%, #1d4f7f 100%);
            color: #f8fbff;
        }
        .calendar-eyebrow {
            display: inline-flex;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .calendar-title { margin-top: 0.9rem; font-size: clamp(1.8rem, 2.8vw, 2.5rem); font-weight: 700; letter-spacing: -0.04em; }
        .calendar-subtitle { max-width: 60rem; margin-top: 0.75rem; color: rgba(248, 251, 255, 0.85); line-height: 1.65; }
        .calendar-toolbar { display: grid; gap: 1rem; min-width: 280px; }
        .calendar-filter { display: grid; gap: 0.45rem; }
        .calendar-filter span { font-size: 0.8rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: rgba(248, 251, 255, 0.8); }
        .calendar-filter select {
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.12);
            color: #f8fbff;
            padding: 0.8rem 0.95rem;
        }
        .calendar-filter option { color: #102033; }
        .calendar-legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: 0.9rem; color: rgba(248, 251, 255, 0.86); }
        .calendar-legend span { display: inline-flex; align-items: center; gap: 0.45rem; }
        .dot { width: 0.7rem; height: 0.7rem; border-radius: 999px; display: inline-block; }
        .dot-high { background: #ef4444; }
        .dot-medium { background: #f59e0b; }
        .dot-low { background: #22c55e; }
        .calendar-kpis { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .calendar-kpi {
            padding: 1.1rem 1.2rem;
            border: 1px solid var(--erp-border);
            border-radius: 20px;
            background: var(--erp-surface);
            box-shadow: var(--erp-shadow);
        }
        .calendar-kpi span { display: block; color: var(--erp-text-muted); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.08em; }
        .calendar-kpi strong { display: block; margin-top: 0.5rem; color: var(--erp-text); font-size: 1.9rem; line-height: 1; letter-spacing: -0.04em; }
        .calendar-shell {
            border: 1px solid var(--erp-border);
            border-radius: 24px;
            background: var(--erp-surface);
            box-shadow: var(--erp-shadow);
            padding: 1.25rem;
        }
        .calendar-shell-header { display: flex; justify-content: space-between; gap: 1rem; align-items: start; margin-bottom: 1rem; }
        .calendar-shell-header h2 { color: var(--erp-text); font-size: 1.1rem; font-weight: 700; }
        .calendar-shell-header p { margin-top: 0.35rem; color: var(--erp-text-muted); }
        .calendar-status {
            align-self: center;
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            background: var(--erp-primary-soft);
            color: var(--erp-primary);
            font-size: 0.85rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .calendar-alert {
            margin-bottom: 1rem;
            border: 1px solid rgba(239, 68, 68, 0.25);
            border-radius: 14px;
            padding: 0.85rem 1rem;
            background: rgba(254, 226, 226, 0.8);
            color: #991b1b;
        }
        .calendar-canvas { min-height: 760px; }
        .fc { --fc-border-color: var(--erp-border); --fc-page-bg-color: transparent; --fc-neutral-bg-color: var(--erp-surface-muted); --fc-today-bg-color: rgba(219, 234, 254, 0.5); --fc-list-event-hover-bg-color: var(--erp-surface-muted); }
        .fc .fc-toolbar-title,
        .fc .fc-col-header-cell-cushion,
        .fc .fc-daygrid-day-number { color: var(--erp-text); }
        .fc .fc-button-primary {
            background: #0f5fbf;
            border-color: #0f5fbf;
            box-shadow: none;
        }
        .fc .fc-button-primary:hover,
        .fc .fc-button-primary:focus { background: #0d4f9d; border-color: #0d4f9d; }
        .fc .fc-event {
            border: none;
            border-radius: 10px;
            padding: 0.2rem 0.35rem;
            box-shadow: 0 8px 16px -14px rgba(15, 23, 42, 0.75);
        }
        .fc .fc-event-title { font-weight: 600; }
        :root.dark .calendar-alert { background: rgba(69, 10, 10, 0.52); color: #fecaca; border-color: rgba(248, 113, 113, 0.3); }
        :root.dark .calendar-filter select { background: rgba(15, 27, 45, 0.8); color: #f8fbff; border-color: rgba(255, 255, 255, 0.12); }
        :root.dark .fc { --fc-today-bg-color: rgba(37, 99, 235, 0.18); }
        :root.dark .fc-theme-standard td,
        :root.dark .fc-theme-standard th,
        :root.dark .fc-theme-standard .fc-scrollgrid { border-color: var(--erp-border); }
        @media (max-width: 1024px) {
            .calendar-hero,
            .calendar-shell-header { grid-template-columns: 1fr; display: grid; }
            .calendar-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .calendar-kpis { grid-template-columns: 1fr; }
            .calendar-canvas { min-height: 680px; }
        }
    </style>

    <script>
        function pendientesCalendar(config) {
            return {
                calendar: null,
                userId: '',
                statusText: 'Cargando agenda...',
                errorMessage: '',
                async init() {
                    try {
                        await this.loadCalendarLibrary();
                        this.renderCalendar();
                    } catch (error) {
                        console.error(error);
                        this.errorMessage = 'No se pudo inicializar el calendario.';
                        this.statusText = 'Error de carga';
                    }
                },
                async loadCalendarLibrary() {
                    if (window.FullCalendar) {
                        return;
                    }

                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                },
                renderCalendar() {
                    this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                        initialView: 'dayGridMonth',
                        locale: 'es',
                        height: 760,
                        editable: true,
                        selectable: false,
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek,listWeek',
                        },
                        events: async (info, successCallback, failureCallback) => {
                            this.statusText = 'Actualizando agenda...';
                            this.errorMessage = '';

                            try {
                                const params = new URLSearchParams({
                                    start: info.startStr,
                                    end: info.endStr,
                                });

                                if (this.userId) {
                                    params.set('user_id', this.userId);
                                }

                                const response = await fetch(`${config.feedUrl}?${params.toString()}`, {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json',
                                    },
                                });

                                if (!response.ok) {
                                    throw new Error('Feed error');
                                }

                                const data = await response.json();
                                successCallback(data);
                                this.statusText = `${data.length} tarea(s) en vista`;
                            } catch (error) {
                                console.error(error);
                                this.errorMessage = 'No se pudieron cargar los pendientes del calendario.';
                                this.statusText = 'Sin datos';
                                failureCallback(error);
                            }
                        },
                        eventClick: (info) => {
                            window.location.href = `/admin/pendientes/${info.event.id}/edit`;
                        },
                        eventDrop: (info) => this.persistEventDate(info),
                        eventResize: (info) => this.persistEventDate(info),
                        eventDidMount: (info) => {
                            const technician = info.event.extendedProps.technician ?? 'Sin asignar';
                            const client = info.event.extendedProps.client ?? 'Sin cliente';
                            info.el.title = `${info.event.title}\nCliente: ${client}\nTecnico: ${technician}`;
                        },
                    });

                    this.calendar.render();
                },
                async persistEventDate(info) {
                    this.statusText = 'Guardando cambio...';
                    this.errorMessage = '';

                    try {
                        const response = await fetch(config.updateUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                id: info.event.id,
                                date: info.event.startStr,
                            }),
                        });

                        if (!response.ok) {
                            throw new Error('Update error');
                        }

                        this.statusText = 'Fecha actualizada';
                    } catch (error) {
                        console.error(error);
                        info.revert();
                        this.errorMessage = 'No se pudo actualizar la fecha del pendiente.';
                        this.statusText = 'Cambio revertido';
                    }
                },
                reloadEvents() {
                    if (this.calendar) {
                        this.calendar.refetchEvents();
                    }
                },
            };
        }
    </script>
</x-filament-panels::page>
