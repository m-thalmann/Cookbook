import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { Router } from '@angular/router';

@Component({
  selector: 'app-search-bar',
  templateUrl: './search-bar.component.html',
  styleUrls: ['./search-bar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SearchBarComponent {
  @Input() initialValue: string | null = null;
  @Input() searchOnSubmit = true;

  @Output() search = new EventEmitter<string>();

  constructor(private router: Router) {}

  doSearch(search: string) {
    search = search.trim();

    this.search.emit(search);

    if (this.searchOnSubmit) {
      this.router.navigate(['search', search]);
    }
  }
}

