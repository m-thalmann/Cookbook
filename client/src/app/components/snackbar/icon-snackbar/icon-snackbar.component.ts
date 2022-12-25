import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { MAT_SNACK_BAR_DATA } from '@angular/material/snack-bar';

@Component({
  selector: 'app-icon-snackbar',
  templateUrl: './icon-snackbar.component.html',
  styleUrls: ['./icon-snackbar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class IconSnackbarComponent {
  constructor(
    @Inject(MAT_SNACK_BAR_DATA)
    public data: {
      message: string;
      icon: string;
      action?: { name: string; isIcon?: boolean; color: 'primary' | 'accent' | 'warn'; callback: () => void };
    }
  ) {}
}

