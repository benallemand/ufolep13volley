export default {
    props: {
        events: {
            type: Array,
            default: () => []
        }
    },
    template: `
        <div class="w-full p-4">
          
            <h2 class="text-2xl font-bold text-center text-primary mb-6">calendrier</h2>
            
            <!-- Légende -->
            <div class="flex justify-center mb-6 gap-4 flex-wrap">
                <div v-for="eventType in eventTypes" :key="eventType.label" class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full" :class="eventType.bgClass"></div>
                    <span class="text-sm">{{ eventType.label }}</span>
                </div>
            </div>

            <!-- Calendrier annuel -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div v-for="month in schoolYearMonths" :key="month.key" class="card bg-base-100 shadow-md">
                    <div class="card-body p-3">
                        <h3 class="card-title text-lg text-center mb-3">{{ month.name }}</h3>
                        
                        <!-- Grille du calendrier -->
                        <div class="grid grid-cols-5 gap-1 text-xs">
                            <!-- En-têtes des jours -->
                            <div v-for="day in ['L', 'M', 'M', 'J', 'V']" 
                                 :key="day" 
                                 class="text-center font-bold text-gray-500 p-1">
                                {{ day }}
                            </div>
                            
                            <!-- Cases vides pour aligner le premier jour -->
                            <div v-for="n in month.startDayWeekdays" :key="'empty-' + n" class="p-1"></div>
                            
                            <!-- Jours du mois (seulement les jours de semaine) -->
                            <div v-for="day in getWeekdaysInMonth(month.year, month.month)" 
                                 :key="day" 
                                 class="relative p-1 text-center hover:bg-base-200 rounded"
                                 :class="getDayClasses(month.year, month.month, day)"
                                 :title="getDayTooltip(month.year, month.month, day)">
                                <span class="relative z-10">{{ day }}</span>
                                
                                
                                <!-- Périodes -->
                                <div v-for="(event, index) in getPeriodEventsForDay(month.year, month.month, day)" 
                                     :key="event.id"
                                     class="absolute inset-x-0 h-1 z-0"
                                     :class="[getEventColor(event).bgClass, getPeriodClasses(month.year, month.month, day, event)]"
                                     :style="{ bottom: (index * 4) + 'px' }"></div>
                            </div>
                        </div>
                        
                        <!-- Liste des événements du mois -->
                        <div v-if="getMonthEvents(month.year, month.month).length > 0" class="mt-3 pt-3 border-t border-base-300">
                            <div v-for="event in getMonthEvents(month.year, month.month)" 
                                 :key="event.id" 
                                 class="text-xs mb-1 p-2 rounded border-l-4"
                                 :class="[getEventColor(event).bgLightClass, getEventColor(event).borderClass]">
                                <div class="font-medium" :class="getEventColor(event).textClass">{{ event.label }}</div>
                                <div class="text-gray-600">{{ formatEventForMonth(event) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `,
    computed: {
        schoolYearMonths() {
            const currentYear = new Date().getFullYear();
            const nextYear = currentYear + 1;
            
            return [
                { name: 'Septembre', month: 8, year: currentYear, key: 'sep' },
                { name: 'Octobre', month: 9, year: currentYear, key: 'oct' },
                { name: 'Novembre', month: 10, year: currentYear, key: 'nov' },
                { name: 'Décembre', month: 11, year: currentYear, key: 'dec' },
                { name: 'Janvier', month: 0, year: nextYear, key: 'jan' },
                { name: 'Février', month: 1, year: nextYear, key: 'feb' },
                { name: 'Mars', month: 2, year: nextYear, key: 'mar' },
                { name: 'Avril', month: 3, year: nextYear, key: 'apr' },
                { name: 'Mai', month: 4, year: nextYear, key: 'may' },
                { name: 'Juin', month: 5, year: nextYear, key: 'jun' }
            ].map(month => ({
                ...month,
                daysInMonth: new Date(month.year, month.month + 1, 0).getDate(),
                startDay: new Date(month.year, month.month, 1).getDay() === 0 ? 6 : new Date(month.year, month.month, 1).getDay() - 1,
                startDayWeekdays: this.getStartDayForWeekdays(month.year, month.month)
            }));
        },
        
        processedEvents() {
            return this.events.map((event, index) => ({
                ...event,
                id: index,
                isPoint: !event.date_end, // Ponctuel si date_end est null
                isPeriod: !!event.date_end, // Période si date_end existe
                startDate: this.parseDate(event.date_start),
                endDate: event.date_end ? this.parseDate(event.date_end) : null
            }));
        },
        
        eventTypes() {
            const types = new Set();
            this.processedEvents.forEach(event => {
                types.add(event.label);
            });
            return Array.from(types).map((label, index) => ({
                label,
                ...this.getColorByIndex(index)
            }));
        }
    },
    methods: {
        parseDate(dateStr) {
            const [datePart, timePart] = dateStr.split(' ');
            const [day, month, year] = datePart.split('/');
            return new Date(year, month - 1, day);
        },
        
        
        getPeriodClasses(year, month, day, event) {
            const checkDate = new Date(year, month, day);
            
            const isFirst = checkDate.getTime() === event.startDate.getTime();
            const isLast = checkDate.getTime() === event.endDate.getTime();
            
            let classes = '';
            if (isFirst) classes += ' rounded-l';
            if (isLast) classes += ' rounded-r';
            
            return classes;
        },
        
        getPointEventsForDay(year, month, day) {
            return this.processedEvents.filter(event => {
                if (!event.isPoint) return false;
                const eventDate = event.startDate;
                return eventDate.getFullYear() === year && 
                       eventDate.getMonth() === month && 
                       eventDate.getDate() === day;
            });
        },
        
        getPeriodEventsForDay(year, month, day) {
            const checkDate = new Date(year, month, day);
            return this.processedEvents.filter(event => {
                if (!event.isPeriod) return false;
                return checkDate >= event.startDate && checkDate <= event.endDate;
            });
        },
        
        getEventColor(event) {
            const eventTypes = Array.from(new Set(this.processedEvents.map(e => e.label)));
            const index = eventTypes.indexOf(event.label);
            return this.getColorByIndex(index);
        },
        
        getColorByIndex(index) {
            const colors = [
                {
                    bgClass: 'bg-blue-500',
                    bgLightClass: 'bg-blue-50',
                    borderClass: 'border-blue-500',
                    textClass: 'text-blue-700'
                },
                {
                    bgClass: 'bg-green-500',
                    bgLightClass: 'bg-green-50',
                    borderClass: 'border-green-500',
                    textClass: 'text-green-700'
                },
                {
                    bgClass: 'bg-purple-500',
                    bgLightClass: 'bg-purple-50',
                    borderClass: 'border-purple-500',
                    textClass: 'text-purple-700'
                },
                {
                    bgClass: 'bg-orange-500',
                    bgLightClass: 'bg-orange-50',
                    borderClass: 'border-orange-500',
                    textClass: 'text-orange-700'
                },
                {
                    bgClass: 'bg-red-500',
                    bgLightClass: 'bg-red-50',
                    borderClass: 'border-red-500',
                    textClass: 'text-red-700'
                },
                {
                    bgClass: 'bg-indigo-500',
                    bgLightClass: 'bg-indigo-50',
                    borderClass: 'border-indigo-500',
                    textClass: 'text-indigo-700'
                },
                {
                    bgClass: 'bg-pink-500',
                    bgLightClass: 'bg-pink-50',
                    borderClass: 'border-pink-500',
                    textClass: 'text-pink-700'
                },
                {
                    bgClass: 'bg-teal-500',
                    bgLightClass: 'bg-teal-50',
                    borderClass: 'border-teal-500',
                    textClass: 'text-teal-700'
                },
                {
                    bgClass: 'bg-yellow-500',
                    bgLightClass: 'bg-yellow-50',
                    borderClass: 'border-yellow-500',
                    textClass: 'text-yellow-700'
                },
                {
                    bgClass: 'bg-cyan-500',
                    bgLightClass: 'bg-cyan-50',
                    borderClass: 'border-cyan-500',
                    textClass: 'text-cyan-700'
                }
            ];
            
            return colors[index % colors.length];
        },
        
        getDayClasses(year, month, day) {
            const today = new Date();
            const checkDate = new Date(year, month, day);
            
            let classes = '';
            
            // Jour actuel
            if (checkDate.toDateString() === today.toDateString()) {
                classes += ' bg-primary text-primary-content font-bold';
            }
            
            // Événements ponctuels - utiliser le style de la première couleur d'événement
            const pointEvents = this.getPointEventsForDay(year, month, day);
            if (pointEvents.length > 0) {
                const eventColor = this.getEventColor(pointEvents[0]);
                // Si c'est aussi le jour actuel, garder le style du jour actuel
                if (checkDate.toDateString() !== today.toDateString()) {
                    classes += ` ${eventColor.bgClass} text-white font-bold`;
                }
            }
            
            return classes;
        },
        
        getDayTooltip(year, month, day) {
            const pointEvents = this.getPointEventsForDay(year, month, day);
            const periodEvents = this.getPeriodEventsForDay(year, month, day);
            
            const allEvents = [...pointEvents, ...periodEvents];
            
            if (allEvents.length === 0) {
                return '';
            }
            
            // Créer le tooltip avec tous les événements du jour
            const eventLabels = allEvents.map(event => {
                if (event.isPoint) {
                    const timeStr = event.date_start.includes(' ') ? event.date_start.split(' ')[1] : '';
                    return timeStr ? `${event.label} (${timeStr})` : event.label;
                } else {
                    return event.label;
                }
            });
            
            return eventLabels.join('\n');
        },
        
        getMonthEvents(year, month) {
            return this.processedEvents.filter(event => {
                if (event.isPoint) {
                    return event.startDate.getFullYear() === year && event.startDate.getMonth() === month;
                } else {
                    // Pour les périodes, inclure si elles touchent ce mois
                    const monthStart = new Date(year, month, 1);
                    const monthEnd = new Date(year, month + 1, 0);
                    return event.startDate <= monthEnd && event.endDate >= monthStart;
                }
            });
        },
        
        formatEventForMonth(event) {
            if (event.isPoint) {
                const timeStr = event.date_start.includes(' ') ? event.date_start.split(' ')[1] : '';
                const dateStr = event.startDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
                return timeStr ? `${dateStr} à ${timeStr}` : dateStr;
            } else {
                return `${event.startDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })} - ${event.endDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })}`;
            }
        },
        
        getStartDayForWeekdays(year, month) {
            const firstDay = new Date(year, month, 1).getDay();
            // Convertir dimanche (0) en 7, puis ajuster pour n'afficher que les jours de semaine
            const adjustedFirstDay = firstDay === 0 ? 7 : firstDay;
            // Si c'est samedi (6) ou dimanche (7), commencer à la semaine suivante
            if (adjustedFirstDay >= 6) {
                return 0; // Pas de cases vides, commencer directement
            }
            return adjustedFirstDay - 1; // Lundi = 0 cases vides
        },
        
        getWeekdaysInMonth(year, month) {
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const weekdays = [];
            
            for (let day = 1; day <= daysInMonth; day++) {
                const dayOfWeek = new Date(year, month, day).getDay();
                // Inclure seulement lundi (1) à vendredi (5)
                if (dayOfWeek >= 1 && dayOfWeek <= 5) {
                    weekdays.push(day);
                }
            }
            
            return weekdays;
        }
    }
};
