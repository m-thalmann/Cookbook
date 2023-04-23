import { Clipboard } from '@angular/cdk/clipboard';
import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'app-share-menu',
  templateUrl: './share-menu.component.html',
  styleUrls: ['./share-menu.component.scss'],
  standalone: true,
  imports: [CommonModule, MatButtonModule, MatIconModule, MatMenuModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ShareMenuComponent {
  @Input() color?: 'primary' | 'accent' | 'warn';
  @Input() url: string = location.href;
  @Input() text?: string;

  @Input() showPrint = false;

  @Input() disabled = false;

  constructor(private clipboard: Clipboard, private snackbar: SnackbarService) {}

  get hasShareApi() {
    return !!navigator.share;
  }

  getText() {
    return this.text || 'Cookbook';
  }

  copyLink() {
    if (this.clipboard.copy(location.href)) {
      this.snackbar.info({ message: 'Link copied!' });
    }
  }

  doPrint() {
    window.print();
  }

  get shareEmailLink() {
    const text = encodeURIComponent(this.getText());
    return `mailto:?subject=${text}&body=${encodeURIComponent(this.url)}`;
  }

  get shareWhatsAppLink() {
    const text = encodeURIComponent(`${this.getText()}\n${this.url}`);
    return `https://wa.me/?text=${text}`;
  }

  get shareTelegramLink() {
    const text = encodeURIComponent(this.getText());
    return `https://t.me/share/url?url=${encodeURIComponent(this.url)}&text=${text}`;
  }

  doShare() {
    navigator.share({
      title: this.text,
      url: this.url,
    });
  }
}
