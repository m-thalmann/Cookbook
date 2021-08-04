import { Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
  selector: 'cb-paginator',
  templateUrl: './paginator.component.html',
  styleUrls: ['./paginator.component.scss'],
})
export class PaginatorComponent {
  @Input() page!: number;
  @Input() pages!: number;

  @Input() disabled: boolean = false;

  @Output() pageChange = new EventEmitter<number>();

  constructor() {}

  setPage(page: number) {
    if (this.page !== page) {
      this.pageChange.emit(page);
    }
    this.page = page;
  }

  get pagesList() {
    let pages: number[] = [];

    for (let i = Math.max(this.page - 1, 2); i <= this.page + 3 && i < this.pages; i++) {
      pages.push(i);
    }

    return pages;
  }
}
