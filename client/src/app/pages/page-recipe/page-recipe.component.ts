import { Clipboard } from '@angular/cdk/clipboard';
import { Component, OnInit } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute, Router } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { calculateTotalTime } from 'src/app/core/functions';
import { SeoService } from 'src/app/core/seo/seo.service';

@Component({
  selector: 'cb-page-recipe',
  templateUrl: './page-recipe.component.html',
  styleUrls: ['./page-recipe.component.scss'],
})
export class PageRecipeComponent implements OnInit {
  loading = false;
  error = false;

  recipe: RecipeFull | null = null;

  selectedPortions: number = 1;

  constructor(
    private route: ActivatedRoute,
    private api: ApiService,
    private user: UserService,
    private snackBar: MatSnackBar,
    private router: Router,
    private clipboard: Clipboard,
    private seo: SeoService
  ) {}

  ngOnInit() {
    this.route.params.subscribe((params) => {
      if (!this.loading) {
        this.load(params.id);
      }
    });
  }

  setSelectedPortions(selectedPortions: Event) {
    if (selectedPortions.target instanceof HTMLInputElement) {
      let value = parseInt(selectedPortions.target.value);

      if (value < 1) {
        this.selectedPortions = 1;
      } else if (!isNaN(value)) {
        this.selectedPortions = value;
      }
    }
  }

  get categoryURL() {
    if (!this.recipe?.category) {
      return null;
    }

    return '/categories/' + encodeURIComponent(this.recipe?.category);
  }

  get totalTime() {
    return calculateTotalTime(this.recipe);
  }

  getCalculateIngredientAmount(amount: number) {
    if (!this.recipe) return amount;

    let calculated = (this.selectedPortions / (this.recipe.portions || 1)) * amount;

    return calculated.toFixed(2).replace(/(?:0*|\.0*)$/g, '');
  }

  get canEdit() {
    return this.recipe && (this.user.user?.isAdmin || this.user.user?.id === this.recipe.user.id);
  }

  async load(id: number) {
    this.loading = true;

    let res = await this.api.getRecipeById(id);

    if (res.isOK()) {
      this.recipe = res.value;
      this.selectedPortions = res.value?.portions || 1;

      // set seo information
      if (res.value) {
        this.seo.setTitle(res.value.name);

        if (res.value.description) {
          this.seo.setDescription(res.value.description);
        }

        if (res.value.imagesCount > 0 && res.value.public) {
          this.seo.setImage(this.api.getRecipeImageURL(res.value.id, 0, 500, false));
        }
      }
    } else if (res.isNotFound()) {
      this.snackBar.open('Recipe was not found', 'OK', {
        panelClass: 'action-warn',
        duration: 5000,
      });

      await this.router.navigateByUrl('/home');
    } else {
      console.error('Error loading recipe:', res.error);
      this.error = true;
    }

    this.loading = false;
  }

  get hasShareApi() {
    return !!navigator.share;
  }

  copyLink() {
    if (this.clipboard.copy(location.href)) {
      this.snackBar.open('Successfully copied!', 'OK', {
        duration: 5000,
      });
    }
  }

  get shareEmailLink() {
    const text = encodeURIComponent(`Recipe: ${this.recipe?.name}`);
    return `mailto:?subject=${text}&body=${encodeURIComponent(location.href)}`;
  }

  get shareWhatsAppLink() {
    const text = encodeURIComponent(`Recipe: ${this.recipe?.name}\n${location.href}`);
    return `https://wa.me/?text=${text}`;
  }

  get shareTelegramLink() {
    const text = encodeURIComponent(`Recipe: ${this.recipe?.name}`);
    return `https://t.me/share/url?url=${encodeURIComponent(location.href)}&text=${text}`;
  }

  doShare() {
    if (!this.recipe) return;

    navigator.share({
      title: `Recipe: ${this.recipe.name}`,
      url: location.href,
    });
  }

  doPrint() {
    window.print();
  }
}
