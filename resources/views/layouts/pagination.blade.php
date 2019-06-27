<?php
$maxShowPageNo = 10; //何ページ分を表示するか
$halfPageNo = floor($maxShowPageNo / 2);

$loopStart = $currentPageNo - $halfPageNo + 1;
if ($loopStart <= 0) {
    $loopStart  = 1;
}
if($currentPageNo > ($totalPageCount-$maxShowPageNo) ){
    $loopStart  = $totalPageCount - $maxShowPageNo + 1;
}


if($currentPageNo < 6){
    $loopEnd = $maxShowPageNo;
}else{
    $loopEnd = $currentPageNo - $halfPageNo + $maxShowPageNo;
}
if($currentPageNo > ($totalPageCount-$maxShowPageNo) ){
    $loopEnd  = $totalPageCount;
}

if ($loopEnd > $totalPageCount) {
    $loopEnd =  $totalPageCount;
}
?>

<div class="page">
  <ul class="pagination">

    <li class="page-item">
      <a class="page-link" href="javascript:void(0);"  onclick="AlueIntegOffice.TaskUtils.firstPage(this)" aria-label="First">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>

    <li class="page-item">
@if($currentPageNo > 1)
      <a class="page-link" href="javascript:void(0);"  onclick="AlueIntegOffice.TaskUtils.previousPage(this)" aria-label="Previous">
        <span aria-hidden="true">Previous</span>
      </a>
@else
      <a class="page-link disabled" href="javascript:void(0);" onclick="return false;" aria-label="Previous">
        <span aria-hidden="true">Previous</span>
      </a>
@endif
    </li>

@for($cnt=$loopStart; $cnt <= $loopEnd; $cnt++)
  @if($cnt > 0 && $cnt <= $totalPageCount)
    @if($cnt == $currentPageNo)
        <li class="page-item current-page"><a class="page-link disabled" href="javascript:void(0);" onclick="return false;">{{ $cnt }}</a></li>
    @else
        <li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.TaskUtils.goPage(this)" data-page-no="{{ $cnt }}">{{ $cnt }}</a></li>
    @endif
  @endif
@endfor

    <li class="page-item">
@if($currentPageNo < $totalPageCount)
      <a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.TaskUtils.nextPage(this)" aria-label="Next">
        <span aria-hidden="true">Next</span>
      </a>
@else
      <a class="page-link disabled" href="javascript:void(0);" onclick="return false;" aria-label="Next">
        <span aria-hidden="true">Next</span>
      </a>
@endif
    </li>

    <li class="page-item">
      <a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.TaskUtils.lastPage(this)" aria-label="Last">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>

@if(!empty($totalRowCount))
    <li class="total-count-item">
      <span aria-hidden="true">全件数：{{$totalRowCount}}件</span>
    </li>
@endif

  </ul>
</div>

