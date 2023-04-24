import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';

/**
 * Variable used to create unique ids for each used searchbar,
 * since the id is needed for the label
 */
let nextIdSuffix = 0;

@Component({
  selector: 'app-search-bar',
  templateUrl: './search-bar.component.html',
  styleUrls: ['./search-bar.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule],
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
