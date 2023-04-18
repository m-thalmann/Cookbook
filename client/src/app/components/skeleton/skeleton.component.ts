import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, HostBinding, Input } from '@angular/core';

@Component({
  selector: 'app-skeleton',
  template: ``,
  styleUrls: ['./skeleton.component.scss'],
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SkeletonComponent {
  @HostBinding('style.width')
  @Input()
  width?: string;

  @HostBinding('style.height')
  @Input()
  height?: string;
}

