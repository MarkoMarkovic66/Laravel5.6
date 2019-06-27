<!-- paging -->
<div class="page" style="margin-top: 20px;">
    <ul class="pagination">
        <li class="page-item">
            <a class="page-link" href="/account/0{{$search_str}}" aria-label="First">
                <span aria-hidden="true">≪</span>
                <span class="sr-only">First</span>
            </a>
        </li>
        <li class="page-item">
            <a class="page-link" href="/account/{{$prev}}{{$search_str}}" aria-label="Previous">
                <span aria-hidden="true">Prev</span>
                <span class="sr-only">Previous</span>
            </a>
        </li>
        @for($i = 0; $i < (int)(count($dataAll)/$limit) + 1; $i++)
            @if($i < $offset + 5 && $i > $offset - 5)
                <li class="page-item">
                    <a href="/account/{{$i}}{{$search_str}}" class="page-link">{{$i + 1}}</a>
                </li>
            @endif
        @endfor
        <li class="page-item">
            <a class="page-link" href="/account/{{$next}}{{$search_str}}" aria-label="Next">
                <span aria-hidden="true">Next</span>
                <span class="sr-only">Next</span>
            </a>
        </li>
        <li class="page-item">
            <a class="page-link" href="/account/{{(int)(count($dataAll)/$limit)}}{{$search_str}}" aria-label="Last">
                <span aria-hidden="true">≫</span>
                <span class="sr-only">Last</span>
            </a>
        </li>

        <li class="total-count-item">
            <span aria-hidden="true">全件数：{{count($dataAll)}}件</span>
        </li>
    </ul>
</div>
<!-- paging -->