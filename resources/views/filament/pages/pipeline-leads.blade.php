<x-filament::page>

<div 
x-data="pipelineBoard()"
class="grid grid-cols-6 gap-6"
>

@foreach($this->getColumns() as $column)

<div 
class="bg-gray-100 rounded-xl p-4 min-h-[500px]"
@drop="dropLead('{{ $column }}')"
@dragover.prevent
>

<h2 class="font-bold mb-4 text-center">
{{ $column }}
</h2>

@foreach($this->getLeads()->get($column, collect()) as $lead)

<div
class="bg-white rounded-lg shadow p-3 mb-3 cursor-move"
draggable="true"
@dragstart="dragLead({{ $lead->id }})"
>

<div class="font-semibold">
{{ $lead->nombre }} {{ $lead->apellido }}
</div>

<div class="text-sm text-gray-500">
📞 {{ $lead->telefono }}
</div>

<div class="text-xs mt-1">

@if($lead->estado_semaforo == 'urgente')
🔴 {{ $lead->dias_sin_seguimiento }} días sin seguimiento
@elseif($lead->estado_semaforo == 'atencion')
🟡 {{ $lead->dias_sin_seguimiento }} días sin seguimiento
@else
🟢 Seguimiento al día
@endif

</div>

</div>

@endforeach

</div>

@endforeach

</div>

<script>

function pipelineBoard(){

return{

leadId:null,

dragLead(id){
this.leadId = id
},

dropLead(status){

$wire.moveLead(this.leadId,status)

setTimeout(()=>{

location.reload()

},300)

}

}

}

</script>

</x-filament::page>