import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, ContentChild, Input, TemplateRef } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { LoadingError } from 'src/app/core/models/loading-error';

@Component({
  selector: 'app-error-display',
  templateUrl: './error-display.component.html',
  styleUrls: ['./error-display.component.scss'],
  standalone: true,
  imports: [CommonModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ErrorDisplayComponent {
  @Input() error!: LoadingError | null;

  @ContentChild('notFoundMessage') notFoundMessageTemplate: TemplateRef<unknown> | undefined;
  @ContentChild('otherErrorMessage') otherErrorMessageTemplate: TemplateRef<unknown> | undefined;
}
