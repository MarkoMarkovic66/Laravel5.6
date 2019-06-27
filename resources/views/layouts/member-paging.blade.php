<div class="page">
  <ul class="pagination">

    <li class="page-item">
      <a class="page-link" href="javascript:void(0);"  onclick="AlueIntegOffice.MemberUtils.firstPage()" aria-label="First">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>

    <li class="page-item">
@if($currentPageNo > 1)
      <a class="page-link" href="javascript:void(0);"  onclick="AlueIntegOffice.MemberUtils.previousPage()" aria-label="Previous">
        <span aria-hidden="true">Previous</span>
      </a>
@else
      <a class="page-link disabled" href="javascript:void(0);" onclick="return false;" aria-label="Previous">
        <span aria-hidden="true">Previous</span>
      </a>
@endif
    </li>

@for($cnt=1; $cnt <= $totalPageCount; $cnt++)
  @if($cnt == $currentPageNo)
    <li class="page-item current-page"><a class="page-link disabled" href="javascript:void(0);" onclick="return false;">{{ $cnt }}</a></li>
  @else
    <li class="page-item"><a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.MemberUtils.goPage({{ $cnt }})" data-page-no="{{ $cnt }}">{{ $cnt }}</a></li>
  @endif
@endfor

    <li class="page-item">
@if($currentPageNo < $totalPageCount)
      <a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.MemberUtils.nextPage()" aria-label="Next">
        <span aria-hidden="true">Next</span>
      </a>
@else
      <a class="page-link disabled" href="javascript:void(0);" onclick="return false;" aria-label="Next">
        <span aria-hidden="true">Next</span>
      </a>
@endif
    </li>

    <li class="page-item">
      <a class="page-link" href="javascript:void(0);" onclick="AlueIntegOffice.MemberUtils.lastPage()" aria-label="Last">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>

    <li class="total-count-item">
      <span aria-hidden="true">全件数：{{$totalRowCount}}件</span>
    </li>

  </ul>
</div>

