import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { Router } from '@angular/router';

/**
 * Variable used to create unique ids for each used searchbar,
 * since the id is needed for the label
 */
let nextIdSuffix = 0;

@Component({
  selector: 'app-search-bar',
  templateUrl: './search-bar.component.html',
  styleUrls: ['./search-bar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SearchBarComponent {
  @Input() initialValue: string | null = null;
  @Input() searchOnSubmit = true;
  @Input() disabled = false;
  @Input() clearable = false;

  @Output() search = new EventEmitter<string>();

  inputId: string = 'search-bar-input-' + nextIdSuffix++;

  constructor(private router: Router) {}

  doSearch(search: string) {
    search = search.trim();

    this.search.emit(search);

    if (this.searchOnSubmit) {
      this.router.navigate(['/recipes'], { queryParams: { search } });
    }
  }

  doClear() {
    this.doSearch('');
  }
}
