<x-filament::page>
    <div 
        x-data="calendarApp()"
        x-init="init()"
        class="space-y-4"
    >

        <!-- 🔎 FILTRO -->
        <select x-model="userId" @change="reloadEvents()" class="border p-2 rounded">
            <option value="">Todos los técnicos</option>
            @foreach(\App\Models\User::where('role','technician')->get() as $user)
                <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
        </select>

        <!-- 📅 CALENDARIO -->
        <div x-ref="calendar"></div>
    </div>

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />

    <script>
    function calendarApp() {
        return {
            calendar: null,
            userId: '',

            init() {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js';

                script.onload = () => this.renderCalendar();

                document.head.appendChild(script);
            },

            renderCalendar() {
                this.calendar = new FullCalendar.Calendar(this.$refs.calendar, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    height: 750,

                    editable: true,
                    selectable: true,

                    events: (info, successCallback) => {
                        let url = '/api/pendientes-calendar';

                        if (this.userId) {
                            url += '?user_id=' + this.userId;
                        }

                        fetch(url)
                            .then(res => res.json())
                            .then(data => successCallback(data));
                    },

                    // 🎯 CLICK → abrir pendiente
                    eventClick: function(info) {
                        window.location.href = '/admin/pendientes/' + info.event.id + '/edit';
                    },

                    // 🔥 DRAG & DROP
                    eventDrop: function(info) {
                        fetch('/api/pendientes-update-date', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id: info.event.id,
                                date: info.event.startStr
                            })
                        });
                    },

                    // 🔥 RESIZE
                    eventResize: function(info) {
                        fetch('/api/pendientes-update-date', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id: info.event.id,
                                date: info.event.startStr
                            })
                        });
                    },

                    // 🎨 TOOLTIP
                    eventDidMount: function(info) {
                        info.el.title = info.event.title;
                    }
                });

                this.calendar.render();
            },

            reloadEvents() {
                this.calendar.refetchEvents();
            }
        }
    }
    </script>
</x-filament::page>